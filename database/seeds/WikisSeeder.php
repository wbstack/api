<?php

use App\QueryserviceNamespace;
use App\User;
use App\Wiki;
use App\WikiDb;
use App\WikiDomain;
use App\WikiManager;
use App\WikiSetting;
use App\WikiSiteStats;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class WikisSeeder extends Seeder {
    /**
     * @param  \App\User  $user
     * @param  string  $name
     * @param  array  $stats
     */
    private function createWiki($user, $name, $stats) {
        $domain = $name . '.nodomain.example';

        $wiki = Wiki::create([
            'sitename' => $name,
            'domain' => $domain,
        ]);

        WikiDomain::create([
            'domain' => $domain,
            'wiki_id' => $wiki->id,
        ]);

        $index = substr(bin2hex(random_bytes(48)), 0, 10);

        WikiDb::create([
            'name' => 'mwdb_' . $index,
            'user' => 'mwu_' . $index,
            'password' => $index,
            'version' => Config::get('wbstack.wiki_db_use_version'),
            'prefix' => 'mwt_' . $index,
            'wiki_id' => $wiki->id,
        ]);

        QueryserviceNamespace::create([
            'namespace' => 'qsns_' . $index,
            'backend' => 'someQueryserviceBackend',
            'wiki_id' => $wiki->id,
        ]);

        WikiSetting::create([
            'name' => 'wgSecretKey',
            'value' => Str::random(64),
            'wiki_id' => $wiki->id,
        ]);

        WikiSetting::create([
            'name' => WikiSetting::wwExtEnableElasticSearch,
            'value' => true,
            'wiki_id' => $wiki->id,
        ]);

        WikiManager::create([
            'user_id' => $user->id,
            'wiki_id' => $wiki->id,
        ]);

        WikiSiteStats::create(
            array_merge($stats, ['wiki_id' => $wiki->id])
        );
    }

    public function run() {
        $email = 'seeder@email.example';
        $user = User::create([
            'email' => $email,
            'password' => Hash::make($email),
            'verified' => true,
        ]);

        for ($id = 0; $id < 50; $id++) {
            $this->createWiki($user, 'seededsite-' . $id, ['pages' => $id]);
        }
    }
}
