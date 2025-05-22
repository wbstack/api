<?php

namespace App\Http\Controllers;

use App\Helper\ProfileValidator;
use App\Jobs\KubernetesIngressCreate;
use App\Jobs\MediawikiInit;
use App\Jobs\ProvisionQueryserviceNamespaceJob;
use App\Jobs\ProvisionWikiDbJob;
use App\Jobs\CirrusSearch\ElasticSearchIndexInit;
use App\Jobs\ElasticSearchAliasInit;
use App\QueryserviceNamespace;
use App\Wiki;
use App\WikiDb;
use App\WikiDomain;
use App\WikiManager;
use App\WikiProfile;
use App\WikiSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;
use App\Helper\DomainValidator;
use App\Helper\DomainHelper;

class WikiController extends Controller
{
    private $domainValidator;
    private $profileValidator;

    public function __construct( DomainValidator $domainValidator, ProfileValidator $profileValidator )
    {
        $this->profileValidator = $profileValidator;
        $this->domainValidator = $domainValidator;
    }

    public function create(Request $request): \Illuminate\Http\Response
    {
        $clusterWithoutSharedIndex = Config::get('wbstack.elasticsearch_cluster_without_shared_index');
        $sharedIndexHost = Config::get('wbstack.elasticsearch_shared_index_host');
        $sharedIndexPrefix = Config::get('wbstack.elasticsearch_shared_index_prefix');

        if (Config::get('wbstack.elasticsearch_enabled_by_default')) {
            if (!$clusterWithoutSharedIndex && !($sharedIndexHost && $sharedIndexPrefix)) {
                abort(503, 'Search enabled, but its configuration is invalid');
            }
        }
        $user = $request->user();

        $submittedDomain = strtolower($request->input('domain'));
        $submittedDomain = DomainHelper::encode($submittedDomain);

        $domainValidator = $this->domainValidator->validate( $submittedDomain );
        $isSubdomain = $this->isSubDomain($submittedDomain);

        $domainValidator->validateWithBag('post');

        // TODO extra validation that username is correct?
        $request->validate([
            'sitename' => 'required|min:3',
            'username' => 'required',
            'profile' => 'nullable|json',
        ]);

        $rawProfile = false;
        if ($request->filled('profile') ) {
            $rawProfile = json_decode($request->input('profile'), true);
            $profileValidator = $this->profileValidator->validate($rawProfile);
            $profileValidator->validateWithBag('post');
        }

        $wiki = null;
        $dbAssignment = null;

        // TODO create with some sort of owner etc?
        DB::transaction(function () use ($user, $request, &$wiki, &$dbAssignment, $isSubdomain, $submittedDomain, $rawProfile) {
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
                abort(403, "Too many wikis. Your new total of {$numWikis} would exceed the limit of {$maxWikis} per user.");
            }

            $wiki = Wiki::create([
                'sitename' => $request->input('sitename'),
                'domain'   => $submittedDomain,
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

            // Create keys for OAuth2
            // T336937
            $keyPair = openssl_pkey_new([
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ]);
            // Extract private key
            openssl_pkey_export($keyPair, $wgOAuth2PrivateKey);
            // Extract pub key
            $keyDetails = openssl_pkey_get_details($keyPair);
            $wgOAuth2PublicKey = $keyDetails['key'];

            WikiSetting::create([
                'wiki_id' => $wiki->id,
                'name' => WikiSetting::wgOAuth2PrivateKey,
                'value' => $wgOAuth2PrivateKey,
            ]);

            WikiSetting::create([
                'wiki_id' => $wiki->id,
                'name' => WikiSetting::wgOAuth2PublicKey,
                'value' => $wgOAuth2PublicKey,
            ]);

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

            // Create WikiProfile
            if ($rawProfile) {
                WikiProfile::create([ 'wiki_id' => $wiki->id, ...$rawProfile ] );
            }


            // TODO maybe always make these run in a certain order..?
            dispatch(new MediawikiInit($wiki->domain, $request->input('username'), $user->email));
            // Only dispatch a job to add a k8s ingress IF we are using a custom domain...
            if (! $isSubdomain) {
                dispatch(new KubernetesIngressCreate($wiki->id, $wiki->domain));
            }
        });

        // dispatch elasticsearch init job to enable the feature
        if (Config::get('wbstack.elasticsearch_enabled_by_default')) {
            if ($clusterWithoutSharedIndex) {
                dispatch(new ElasticSearchIndexInit($wiki->id, $clusterWithoutSharedIndex));
            }
            if ($sharedIndexHost && $sharedIndexPrefix) {
                dispatch(new ElasticSearchAliasInit($wiki->id));
            }
        }

        // opportunistic dispatching of jobs to make sure storage pools are topped up
        dispatch(new ProvisionWikiDbJob(null, null, 10));
        dispatch(new ProvisionQueryserviceNamespaceJob(null, 10));

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
        $wikiDeletionReason = $request->input('deletionReasons');
        $wiki = $request->attributes->get('wiki');

        if(isset($wikiDeletionReason)){
            //Save the wiki deletion reason
            $wiki->update(['wiki_deletion_reason' => $wikiDeletionReason]);
        }
        // Delete the wiki
        $wiki->delete();

        // Return a success
        return response()->json("Success", 200);
    }

    // TODO should this just be get wiki?
    public function getWikiDetailsForIdForOwner(Request $request): \Illuminate\Http\Response
    {
        $wiki = $request->attributes->get('wiki')
            ->with('wikiManagers')
            ->with('wikiDbVersion')
            ->with('wikiLatestProfile')
            ->with('publicSettings')->first();

        $res = [
            'success' => true,
            'data'    => $wiki,
        ];

        return response($res);
    }

    public static function isSubDomain( string $domain, string $subDomainSuffix = null  ): bool {
        $subDomainSuffix = $subDomainSuffix ?? Config::get('wbstack.subdomain_suffix');
        return preg_match('/' . preg_quote( $subDomainSuffix ) . '$/', $domain) === 1;
    }
}
