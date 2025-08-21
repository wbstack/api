<?php

namespace App\Http\Controllers\Sandbox;

use App\Http\Controllers\Controller;
use App\Jobs\MediawikiSandboxLoadData;
use App\Jobs\ProvisionQueryserviceNamespaceJob;
use App\Jobs\ProvisionWikiDbJob;
use App\QueryserviceNamespace;
use App\Wiki;
use App\WikiDb;
use App\WikiDomain;
use App\WikiSetting;
use Hackzilla\PasswordGenerator\Generator\HumanPasswordGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SandboxController extends Controller {
    const WIKIBASE_DOMAIN = 'wikibase.cloud';

    const MW_VERSION = 'mw1.35-wbs1';

    const DOT = '.';

    public function create(Request $request): \Illuminate\Http\Response {
        $validation = [
            'recaptcha' => 'required|captcha',
            // TODO validate dataSet param
        ];
        $validator = Validator::make($request->all(), $validation);
        $validator->validate();

        $domain = $this->generateDomain();
        $dataSet = $request->get('dataSet');

        $wiki = null;
        DB::transaction(function () use (&$wiki, $domain) {
            $wikiDbCondition = ['wiki_id' => null, 'version' => self::MW_VERSION];

            // Fail if there is not enough storage ready
            if (WikiDb::where($wikiDbCondition)->count() == 0) {
                abort(503, 'No databases ready');
            }
            if (QueryserviceNamespace::where('wiki_id', null)->count() == 0) {
                abort(503, 'No query namespaces ready');
            }

            $wiki = Wiki::create([
                'sitename' => 'Sandbox',
                'domain' => strtolower($domain),
            ]);

            // Assign storage
            $dbAssignment = DB::table('wiki_dbs')->where($wikiDbCondition)->limit(1)->update(['wiki_id' => $wiki->id]);
            if (! $dbAssignment) {
                abort(503, 'Database ready, but failed to assign');
            }
            $nsAssignment = DB::table('queryservice_namespaces')->where(['wiki_id' => null])->limit(1)->update(['wiki_id' => $wiki->id]);
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

            WikiSetting::create([
                'wiki_id' => $wiki->id,
                'name' => 'wwSandboxAutoUserLogin',
                'value' => '1',
            ]);

            // Also track the domain forever in your domains table
            $wikiDomain = WikiDomain::create([
                'domain' => $wiki->domain,
                'wiki_id' => $wiki->id,
            ]);
        });

        // For now only schedule this job for 1 dataSet type
        // As the extension API is dumb currently
        if ($dataSet === 'library') {
            dispatch(new MediawikiSandboxLoadData($domain, $dataSet));
        }

        // opportunistic dispatching of jobs to make sure storage pools are topped up
        dispatch(new ProvisionWikiDbJob(null, null, 10));
        dispatch(new ProvisionQueryserviceNamespaceJob(null, 10));

        $res['success'] = true;
        $res['message'] = 'Success!';
        $res['data'] = [
            'id' => $wiki->id,
            'domain' => $wiki->domain,
        ];

        return response($res);
    }

    private function generateUnusedDomain() {
        $domain = $this->generateDomain();
        if (! WikiDomain::whereDomain($domain)->first()) {
            return $domain;
        }

        return $this->generateUnusedDomain();
    }

    private function generateDomain(): string {
        $generator = new HumanPasswordGenerator;

        $generator
            ->setWordList(__DIR__ . '/words')
            ->setWordCount(3)
            ->setWordSeparator('-');

        $password = $generator->generatePassword();

        return $password . self::DOT . self::WIKIBASE_DOMAIN;
    }
}
