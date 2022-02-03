<?php

namespace App\Jobs;

use App\Http\Controllers\WikiController;
use App\QueryserviceNamespace;
use App\User;
use App\Wiki;
use App\WikiDomain;
use App\WikiManager;
use App\WikiSetting;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\DB;

/*
    - This will create a laravel Wiki object and persist it
    - This Wiki object will be owned by the user with the same email address
    - A WikiDb will exist; it will have no tables but it will have grants for a) the wiki manager user and b) a specific user for this db
    - The wiki settings will be attached to the Wiki
    - A queryservice namespace will be assigned to this Wiki
*/

class MigrationWikiCreate extends Job
{
    private $email;
    private $wikiDetailsFilepath;

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

        if (! is_readable($this->wikiDetailsFilepath)) {
            $this->fail( new \RuntimeException('Error: the wiki-details json file is not readable (does the file exist? is the path correct?') );
            return;
        }

        $wikiDetails = json_decode(file_get_contents($this->wikiDetailsFilepath));

        foreach ($wikiDetails->settings as $setting) {
            if ($setting->name === "wikibaseFedPropsEnable") {
                if ($setting->value == '1') {
                    die("Migration aborted; Feddy props detected!");
                }
                break;
            }
        }

        $prefix = $wikiDetails->wiki_db->prefix;
        $emptyWikiDbJob = new CreateEmptyWikiDb($prefix);
        $emptyWikiDbJob->handle($manager);
        $wikiDb = $emptyWikiDbJob->getWikiDb();

        $user = User::firstOrCreate(
            [ 'email' => $this->email ],
            [ 'password' => 'nothing-hashes-to-me' ] // this should mean the user can't login since nothing will ever hash to this
        );
        $this->createWiki($user, $wikiDetails, $wikiDb);
    }

    /**
     * @param $user
     * @param $wikiDetails
     * @param $wikiDb
     * @return Wiki
     */
    private function createWiki($user, $wikiDetails, $wikiDb) {
        $wiki = null;

        DB::transaction(function () use (&$wiki, $user, $wikiDetails, $wikiDb) {
            $wiki = Wiki::create([
                'sitename' => $wikiDetails->sitename,
                'domain' => strtolower($wikiDetails->domain),
            ]);

            $wikiDb->wiki_id = $wiki->id;
            $wikiDb->save();

            $nsAssignment = QueryserviceNamespace::firstWhere(['wiki_id' => null]);
            $nsAssignment->wiki_id = $wiki->id;
            $nsAssignment->save();

            foreach ($wikiDetails->settings as $setting) {
                    WikiSetting::create([
                        'wiki_id' => $wiki->id,
                        'name' => $setting->name,
                        'value' => $setting->value,
                    ]);
            }

            // Also track the domain forever in the domains table
            $wikiDomain = WikiDomain::firstWhere(['domain' => "$wiki->domain"]);
            $wikiDomain->wiki_id = $wiki->id;
            $wikiDomain->save();

            WikiManager::create([
                'user_id' => $user->id,
                'wiki_id' => $wiki->id,
            ]);
        });

        // Create k8s ingress if the wiki uses a custom domain
        if ( isset($wiki->domain) && ! WikiController::isSubDomain($wiki->domain)) {
            dispatch(new KubernetesIngressCreate($wiki->id, $wiki->domain));
        }

        return $wiki;
    }
}
