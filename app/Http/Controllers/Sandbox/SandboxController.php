<?php


namespace App\Http\Controllers\Sandbox;


use App\Http\Controllers\Controller;
use App\Jobs\KubernetesIngressCreate;
use App\Jobs\MediawikiInit;
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

class SandboxController extends Controller {

    public function create(Request $request)
    {

        $domain = $this->generateDomain();

        return response($domain);
        $wiki = null;
        $dbAssignment = null;
        // TODO create with some sort of owner etc?
        DB::transaction(function () use ($user, $request, &$wiki, &$dbAssignment, $isSubdomain) {
            $wikiDbCondition = ['wiki_id'=>null,'version'=>'mw1.35-wbs1'];

            // Fail if there is not enough storage ready
            if (WikiDb::where($wikiDbCondition)->count() == 0) {
                abort(503, 'No databases ready');
            }
            if (QueryserviceNamespace::where('wiki_id', null)->count() == 0) {
                abort(503, 'No query namespaces ready');
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
                'name' => 'wgSecretKey',
                'value' => Str::random(64),
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

            // If we are local, the dev environment wont be able to run these jobs yet, so end this closure early.
            // TODO maybe send different jobs instead? or do this in the jobs?
            if( App::environment() === 'local' ) {
                return;
            }

            // TODO maybe always make these run in a certain order..?
            $this->dispatch(new MediawikiInit($wiki->domain, $request->input('username'), $user->email));
            // Only dispatch a job to add a k8s ingress IF we are using a custom domain...
            if (!$isSubdomain) {
                $this->dispatch(new KubernetesIngressCreate( $wiki->id, $wiki->domain ));
            }
        });

        $res['success'] = true;
        $res['message'] = 'Success!';
        $res['data'] = [
            'id' => $wiki->id,
            'name' => $wiki->name,
            'domain' => $wiki->domain,
        ];

        return response('Hello World');
    }

    private function generateDomain()
    {
        return 'test.wikibase.cloud';
    }
}