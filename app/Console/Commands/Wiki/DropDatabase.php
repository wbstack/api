<?php

namespace App\Console\Commands\Wiki;

use App\Wiki;
use App\WikiDb;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\App;

class dropDatabase extends Command {
    protected $signature = 'wbs-wiki:dropDatabase {wikiDomain}';

    protected $description = 'Drops the MediaWiki database of a wiki and soft-deletes it.';

    public function handle(): int {
        $wikiDomain = trim($this->argument('wikiDomain'));
        $wiki = Wiki::with('wikidb')->firstWhere('domain', $wikiDomain);

        if (!$wiki) {
            $this->error("Wiki not found by domain '$wikiDomain'");

            return 1;
        }

        if (!$this->dropWikiDb($wiki->wikidb)) {
            $this->error('Failed to drop the mediawiki database of wiki.');
            $this->error($wiki);

            return 2;
        }

        $this->info('MediaWiki database dropped.');

        $wiki->delete();

        $this->info('Wiki soft-deleted.');
        $this->info($wiki);

        return 0;
    }

    private function dropWikiDb(WikiDb $wikiDb): bool {
        $connection = $this->getWikiDbConnection($wikiDb);

        if (!$connection instanceof \Illuminate\Database\Connection) {
            throw new \RuntimeException('Must be run on a PDO based DB connection');
        }

        $mediawikiPdo = $connection->getPdo();
        $statement = $mediawikiPdo->prepare('DROP DATABASE ' . $wikiDb->name);

        return $statement->execute([$wikiDb->name]);
    }

    // Creates a temporary DB connection with wiki scoped credentials
    private function getWikiDbConnection(WikiDb $wikiDb): mixed {
        $wikiDbConnectionConfig = array_replace(
            app()->config->get('database.connections.mw'),
            [
                'database' => $wikiDb->name,
                'username' => $wikiDb->user,
                'password' => $wikiDb->password,
            ]
        );

        app()->config->set('database.connections.wikiTemp', $wikiDbConnectionConfig);

        return App::make(DatabaseManager::class)->connection('wikiTemp');
    }
}
