<?php

namespace App\Console\Commands\Wiki;

use App\Wiki;
use App\WikiDb;
use Illuminate\Support\Facades\App;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;

class Delete extends Command {
    public const SUCCESS = 'Success!';

    public const ERR_WIKI_DOES_NOT_EXIST = 'No wiki was found matching the given key and value.';

    public const ERR_AMBIGUOUS_KEY_VALUE = 'Wiki deletion failed. Multiple wikis match the given key and value.';

    public const ERR_FAILED_DATA_DELETION = 'Deleting data in the wikis database failed.';

    protected $signature = 'wbs-wiki:delete {key} {value}';

    protected $description = 'Soft deletes the Wiki matching the given key and value, while cleaning up some user data.';

    public function handle(): int {
        $key = trim($this->argument('key'));
        $value = trim($this->argument('value'));

        $wikis = Wiki::with('wikidb')->where($key, $value);

        if ($wikis->count() === 0) {
            $this->error(self::ERR_WIKI_DOES_NOT_EXIST);

            return 1;
        } elseif ($wikis->count() > 1) {
            $this->error(self::ERR_AMBIGUOUS_KEY_VALUE);

            return 2;
        }

        $wiki = $wikis->first();

        if (!$this->cleanupUserData($wiki)) {
            $this->error(self::ERR_FAILED_DATA_DELETION);
            $this->error($wiki);

            return 3;
        }

        $wiki->delete();

        $this->info(self::SUCCESS);

        return 0;
    }

    private function cleanupUserData($wiki): bool {
        $wikiDb = $wiki->wikidb;
        $prefix = $wikiDb->prefix;

        // Replaces current mw database connection config with scoped wiki credentials
        app()->config->set('database.connections.mw.database', $wikiDb->name);
        app()->config->set('database.connections.mw.username', $wikiDb->user);
        app()->config->set('database.connections.mw.password', $wikiDb->password);

        $manager = App::make(DatabaseManager::class);
        $mwConn = $manager->connection('mw');

        if (!$mwConn instanceof \Illuminate\Database\Connection) {
            throw new \RuntimeException('Must be run on a PDO based DB connection');
        }

        $mediawikiPdo = $mwConn->getPdo();
        $statement = $mediawikiPdo->prepare("UPDATE ${prefix}_user SET user_real_name = '', user_email = '', user_password = ''");

        return $statement->execute();
    }
}
