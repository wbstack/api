<?php

namespace App\Jobs;

use App\QueryserviceNamespace;
use App\User;
use App\Wiki;
use App\WikiDb;
use App\WikiDomain;
use App\WikiManager;
use App\WikiSetting;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Lcobucci\JWT\Exception;

/*
    - This will create a laravel Wiki object and persist it
    - This Wiki object will be owned by a user that has the email address
    - A WikiDb will exist; it will have no tables but it will have grants for a) the wiki manager user and b) a specific user for this db
    - The wiki settings will be attached to the Wiki
    - A queryservice namespace will be assigned to this Wiki
*/

class MigrationWikiCreate extends Job
{
    private $wikiDetailsFilepath;
    private $email;

    /**
     * @param string $email the email of the user to own this wiki
     * @param string $wikiDetailsFilepath file path to a `wiki-details.json` file, created with `php artisan wbs-wiki:get domain $DOMAIN`
     *
     * @return void
     */
    public function __construct($email, $wikiDetailsFilepath)
    {
        $this->email = $email;
        $this->wikiDetailsFilepath = $wikiDetailsFilepath;
    }

    /**
     * @return void
     */
    public function handle( DatabaseManager $manager )
    {
        $user = User::where('email', $this->email)->get();

        if ($user->count() !== 1) {
            $this->fail( new \RuntimeException('Error: there were '.$user->count().' users found with `email`: '.$this->email) );
            return;
        }

        if (! is_readable($this->wikiDetailsFilepath)) {
            $this->fail( new \RuntimeException('Error: the wiki-details json file is not readable (does the file exist? is the path correct?') );
            return;
        }

        $wikiDetails = json_decode(file_get_contents($this->wikiDetailsFilepath));

        $wikiDb = $this->createWikiDb($manager);
        $this->createWiki($user, $wikiDetails, $wikiDb);

        return true;
    }

    /**
     * @return WikiDb
     */
    private function createWikiDb($manager) {
        $manager->purge('mw');
        $conn = $manager->connection('mw');
        if (! $conn instanceof \Illuminate\Database\Connection) {
            $this->fail(new \RuntimeException('Must be run on a PDO based DB connection'));

            return; //safegaurd
        }
        $pdo = $conn->getPdo();

        $wikiDb = WikiDb::create([
            'name' => $this->dbName,
            'user' => $this->dbUser,
            'password' => $this->dbPassword,
            'version' => $this->newSqlFile,
            'prefix' => $this->prefix,
        ]);

        $manager->purge('mw');

        return $wikiDb;
    }

    /**
     * @return void
     */
    private function createWiki($user, $wikiDetails, $wikiDb) {
        DB::transaction(function () use ($user, $wikiDetails, &$wiki, &$dbAssignment) {
            $dbVersion = Config::get('wbstack.wiki_db_use_version');
            $wikiDbCondition = ['wiki_id'=>null, 'version'=>$dbVersion];

            $wiki = Wiki::create([
                'sitename' => $wikiDetails->sitename,
                'domain' => strtolower($wikiDetails->domain),
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

            // Create the enabled elasticsearch setting
            // T285541
            WikiSetting::create([
                'wiki_id' => $wiki->id,
                'name' => WikiSetting::wwExtEnableElasticSearch,
                'value' => true,
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
        });
    }
}
