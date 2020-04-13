<?php

namespace App\Http\Controllers;

use App\Jobs\KubernetesIngressCreate;
use App\Wiki;
use App\WikiDb;
use App\WikiDomain;
use App\WikiManager;
use App\WikiSetting;
use App\Jobs\MediawikiInit;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\QueryserviceNamespace;
use Illuminate\Support\Facades\DB;
use App\Jobs\MediawikiQuickstatementsInit;

class WikiController extends Controller
{
    public function create(Request $request)
    {
        $user = $request->user();

        $submittedDomain = $request->input('domain');
        $isSubdomain = preg_match( '/.wiki\.opencura\.com$/', $submittedDomain );
        if ( $isSubdomain ) {
            // .wiki.opencura.com is 18 characters long
            // if we want at least 5 chars for the site sub domain
            // that is 23 length
            // This also stops things like mail. www. pop. ETC...
            $domainRequirements = 'required|unique:wikis|unique:wiki_domains|min:23|regex:/^[a-z0-9-]+\.wiki\.opencura\.com$/';
        } else {
            $domainRequirements = 'required|unique:wikis|unique:wiki_domains|min:4|regex:/[a-z0-9-]+\.[a-z]+$/';
        }

        // TODO extra validation that username is correct?
        $request->validate([
            'domain' => $domainRequirements,
            'sitename' => 'required|min:3',
            'username' => 'required',
        ]);

        $wiki = null;
        $dbAssignment = null;
        // TODO create with some sort of owner etc?
        DB::transaction(function () use ($user, $request, &$wiki, &$dbAssignment, $isSubdomain) {
            // Fail if there is not enough storage ready
            if (WikiDb::where('wiki_id', null)->count() == 0) {
                abort(503, 'No databases ready');
            }
            if (QueryserviceNamespace::where('wiki_id', null)->count() == 0) {
                abort(503, 'No query namespaces ready');
            }

            $wiki = Wiki::create([
                'sitename' => $request->input('sitename'),
                'domain' => strtolower($request->input('domain')),
            ]);

            // Also track the domain forever in your domains table
            $wikiDomain = WikiDomain::create([
                'domain' => $wiki->domain,
                'wiki_id' => $wiki->id,
            ]);

            // Assign storage
            $dbAssignment = DB::table('wiki_dbs')->where(['wiki_id'=>null])->limit(1)->update(['wiki_id' => $wiki->id]);
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

            $ownerAssignment = WikiManager::create([
              'user_id' => $user->id,
              'wiki_id' => $wiki->id,
            ]);

            // TODO maybe always make these run in a certain order..?
            dispatch(new MediawikiInit($wiki->domain, $request->input('username'), $user->email));
            dispatch(new MediawikiQuickstatementsInit($wiki->domain));
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

        return response($res);
    }

    public function delete(Request $request) {
        $user = $request->user();

        $request->validate([
            'wiki' => 'required|numeric',
        ]);

        $wikiId = $request->input('wiki');
        $userId = $user->id;

        // Check that the requesting user manages the wiki
        if( WikiManager::where( 'user_id', $userId )->where( 'wiki_id', $wikiId )->count() !== 1 ) {
            // The deletion was requested by a user that does not manage the wiki
            return response()->json('Unauthorized', 401);
        }

        // Delete the wiki
        Wiki::find( $wikiId )->delete();

        // Return a success
        return response()->json('Success', 200);
    }

    // TODO should this just be get wiki?
    public function getWikiDetailsForIdForOwner(Request $request)
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
      ->first();

        $res['success'] = true;
        $res['data'] = $wiki;

        return response($res);
    }
}
