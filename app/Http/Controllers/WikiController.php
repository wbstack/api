<?php

namespace App\Http\Controllers;

use App\Jobs\KubernetesIngressCreate;
use App\Jobs\MediawikiInit;
use App\Jobs\ProvisionQueryserviceNamespaceJob;
use App\Jobs\ProvisionWikiDbJob;
use App\Jobs\CirrusSearch\ElasticSearchIndexInit;
use App\QueryserviceNamespace;
use App\Wiki;
use App\WikiDb;
use App\WikiDomain;
use App\WikiManager;
use App\WikiSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;
use App\Helper\DomainValidator;

class WikiController extends Controller
{
    private $domainValidator;

    public function __construct( DomainValidator $domainValidator )
    {
        $this->domainValidator = $domainValidator;
    }

    public function create(Request $request): \Illuminate\Http\Response
    {
        $user = $request->user();
        $submittedDomain = strtolower($request->input('domain'));
        
        $validator = $this->domainValidator->validate( $submittedDomain );
        $isSubdomain = $this->isSubDomain($submittedDomain);

        $validator->validateWithBag('post');

        // TODO extra validation that username is correct?
        $request->validate([
            'sitename' => 'required|min:3',
            'username' => 'required',
        ]);

        $wiki = null;
        $dbAssignment = null;

        // TODO create with some sort of owner etc?
        DB::transaction(function () use ($user, $request, &$wiki, &$dbAssignment, $isSubdomain) {
            $dbVersion = Config::get('wbstack.wiki_db_use_version');
            $wikiDbCondition = ['wiki_id'=>null, 'version'=>$dbVersion];

            // Fail if there is not enough storage ready
            if (WikiDb::where($wikiDbCondition)->count() == 0) {
                abort(503, 'No databases ready');
            }
            if (QueryserviceNamespace::where('wiki_id', null)->count() == 0) {
                abort(503, 'No query namespaces ready');
            }

            $numWikis = $user->managesWikis()->count() + 1;
            $maxWikis = config('wbstack.wiki_max_per_user');

            if ( config('wbstack.wiki_max_per_user') !== false && $numWikis > config('wbstack.wiki_max_per_user')) {
                abort(403, "Too many wikis. Your new total of {$numWikis} would exceed the limit of ${maxWikis} per user.");
            }

            $wiki = Wiki::create([
                'sitename' => $request->input('sitename'),
                'domain' => strtolower($request->input('domain')),
            ]);

            // Assign storage
            $dbAssignment = DB::table('wiki_dbs')->where($wikiDbCondition)->limit(1)->update(['wiki_id' => $wiki->id]);
            if (! $dbAssignment) {
                abort(503, 'Database ready, but failed to assign');
            }
            $nsAssignment = DB::table('queryservice_namespaces')->where(['wiki_id'=>null])->limit(1)->update(['wiki_id' => $wiki->id]);
            if (! $nsAssignment) {
                abort(503, 'QS Namespace ready, but failed to assign');
            }

            // Create initial needed non default settings
            // Docs: https://www.mediawiki.org/wiki/Manual:$wgSecretKey
            WikiSetting::create([
                'wiki_id' => $wiki->id,
                'name' => WikiSetting::wgSecretKey,
                'value' => Str::random(64),
            ]);

            // Create the elasticsearch setting
            // T314937
            WikiSetting::create([
                'wiki_id' => $wiki->id,
                'name' => WikiSetting::wwExtEnableElasticSearch,
                'value' => Config::get('wbstack.elasticsearch_enabled_by_default'),
            ]);

            // Also track the domain forever in your domains table
            $wikiDomain = WikiDomain::create([
                'domain' => $wiki->domain,
                'wiki_id' => $wiki->id,
            ]);

            $ownerAssignment = WikiManager::create([
              'user_id' => $user->id,
              'wiki_id' => $wiki->id,
            ]);

            // TODO maybe always make these run in a certain order..?
            $this->dispatch(new MediawikiInit($wiki->domain, $request->input('username'), $user->email));
            // Only dispatch a job to add a k8s ingress IF we are using a custom domain...
            if (! $isSubdomain) {
                $this->dispatch(new KubernetesIngressCreate($wiki->id, $wiki->domain));
            }
        });


        // dispatch elasticsearch init job to enable the feature
        if ( Config::get('wbstack.elasticsearch_enabled_by_default') ) {
            $this->dispatch(new ElasticSearchIndexInit($wiki->id));
        }
        
        // opportunistic dispatching of jobs to make sure storage pools are topped up
        $this->dispatch(new ProvisionWikiDbJob(null, null, 10));
        $this->dispatch(new ProvisionQueryserviceNamespaceJob(null, 10));

        $res['success'] = true;
        $res['message'] = 'Success!';
        $res['data'] = [
            'id' => $wiki->id,
            'name' => $wiki->name,
            'domain' => $wiki->domain,
        ];

        return response($res);
    }

    public function delete(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'wiki' => 'required|numeric',
        ]);

        $wikiId = $request->input('wiki');
        $userId = $user->id;

        // Check that the requesting user manages the wiki
        if (WikiManager::where('user_id', $userId)->where('wiki_id', $wikiId)->count() !== 1) {
            // The deletion was requested by a user that does not manage the wiki
            return response()->json('Unauthorized', 401);
        }

        // Delete the wiki
        Wiki::find($wikiId)->delete();

        // Return a success
        return response()->json('Success', 200);
    }

    // TODO should this just be get wiki?
    public function getWikiDetailsForIdForOwner(Request $request): \Illuminate\Http\Response
    {
        $user = $request->user();

        $wikiId = $request->input('wiki');

        // TODO general check to make sure current user can manage the wiki
        // this should probably be middle ware?
        // TODO only do 1 query where instead of 2?
        $test = WikiManager::where('user_id', $user->id)
      ->where('wiki_id', $wikiId)
      ->first();
        if (! $test) {
            abort(403);
        }

        $wiki = Wiki::where('id', $wikiId)
      ->with('wikiManagers')
      ->with('wikiDbVersion')
      ->with('publicSettings')->first();

        $res['success'] = true;
        $res['data'] = $wiki;

        return response($res);
    }

    public static function isSubDomain( string $domain, string $subDomainSuffix = null  ): bool {
        $subDomainSuffix = $subDomainSuffix ?? Config::get('wbstack.subdomain_suffix');
        return preg_match('/' . preg_quote( $subDomainSuffix ) . '$/', $domain) === 1;
    }
}
