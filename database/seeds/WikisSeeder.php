<?php

use App\Wiki;
use App\WikiDomain;
use App\WikiManager;
use App\WikiSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WikisSeeder extends Seeder
{
    public function run()
    {
        // Just create one for now
        // TODO this is all done in the WikiController but should probably be factored out so it can be reused...
        $wiki = Wiki::create([
            'sitename' => 'seededSite',
            'domain' => 'seededsite.nodomain.dev',
        ]);
        WikiDomain::create([
            'domain' => 'seededsite.nodomain.dev',
            'wiki_id' => $wiki->id,
        ]);
        DB::table('wiki_dbs')->where(['wiki_id'=>null])->limit(1)->update(['wiki_id' => $wiki->id]);
        DB::table('queryservice_namespaces')->where(['wiki_id'=>null])->limit(1)->update(['wiki_id' => $wiki->id]);
        WikiSetting::create([
            'wiki_id' => $wiki->id,
            'name' => 'wgSecretKey',
            'value' => Str::random(64),
        ]);

        WikiSetting::create([
            'wiki_id' => $wiki->id,
            'name' => WikiSetting::wwExtEnableElasticSearch,
            'value' => true,
        ]);

        WikiManager::create([
            'user_id' => DB::table('users')->where(['email'=>'adamshorland@gmail.com'])->limit(1)->get()->pop()->id,
            'wiki_id' => $wiki->id,
        ]);
    }
}
