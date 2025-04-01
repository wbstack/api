<?php

namespace Tests\Metrics;

use App\Jobs\ProvisionWikiDbJob;
use App\Metrics\App\WikiMetrics;
use App\Wiki;
use App\WikiDailyMetrics;
use App\WikiDb;
use Carbon\Carbon;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WikiMetricsTest extends TestCase
{ // Todo: Test that the added metrics are saved in the right frequency and the right values.
    use RefreshDatabase;

    protected function tearDown(): void {
        Wiki::query()->delete();
        WikiDb::query()->delete();
        parent::tearDown();
    }
    public function testSuccessfullyAddRecords()
    {
        $wiki = Wiki::factory()->create([
            'domain' => 'thisfake.wikibase.cloud'
        ]);
        $manager = $this->app->make('db');
        $this->creatWikiDb($wiki, $manager);
        (new WikiMetrics())->saveMetrics($wiki, $manager);
        // Assert the metric is updated in the database
        $this->assertDatabaseHas('wiki_daily_metrics', [
            'date' => now()->toDateString()
        ]);
    }


    public function testDoesNotAddDuplicateRecordsWithOnlyDateChange()
    {
        $wiki = Wiki::factory()->create([
            'domain' => 'thisfake.wikibase.cloud'
        ]);
        $manager = $this->app->make('db');
        $this->creatWikiDb($wiki, $manager);
        //Insert an old metric value for a wiki
        WikiDailyMetrics::create([
            'id' => $wiki->id. '_'. Carbon::yesterday()->toDateString(),
            'wiki_id' => $wiki->id,
            'date' => Carbon::yesterday()->toDateString(),
            'pages' => 0,
            'is_deleted' => 0
        ]);
        (new WikiMetrics())->saveMetrics($wiki, $manager);

        //Assert No new record was created for today
        $this->assertDatabaseMissing('wiki_daily_metrics', [
            'wiki_id' => $wiki->id,
            'date' => Carbon::today()->toDateString()
        ]);
    }

    public function testAddRecordsWikiIsDeleted()
    {
        $wiki = Wiki::factory()->create([
            'domain' => 'thisfake.wikibase.cloud'
        ]);
        $manager = $this->app->make('db');
        $this->creatWikiDb($wiki, $manager);
        //Insert an old metric value for a wiki
        WikiDailyMetrics::create([
            'id' => $wiki->id. '_'. Carbon::yesterday()->toDateString(),
            'wiki_id' => $wiki->id,
            'date' => Carbon::yesterday()->toDateString(),
            'pages' => 0,
            'is_deleted' => 1
        ]);
        //delete the wiki
        $wiki->delete();
        $wiki->save();

        (new WikiMetrics())->saveMetrics($wiki, $manager);

        //Assert No new record was created for today
        $this->assertDatabaseMissing('wiki_daily_metrics', [
            'wiki_id' => $wiki->id,
            'is_deleted' => 1,
            'date' => now()->toDateString()
        ]);
    }

    private function creatWikiDb(Wiki $wiki, DatabaseManager $manager){
        $job = new ProvisionWikiDbJob(null, null, 1);
        $job->handle($manager);
        $db = DB::table('wiki_dbs')->where(["wiki_id" => null])->limit(1)->value('name');
        $db_prefix = DB::table('wiki_dbs')->where(["wiki_id" => null])->limit(1)->value('prefix');
        DB::table('wiki_dbs')->where(["wiki_id" => null])->update(['wiki_id' => $wiki->id]);
        $manager->purge('mw');
        $conn = $manager->connection('mw');
        if (! $conn instanceof \Illuminate\Database\Connection) {
            $this->fail(new \RuntimeException('Must be run on a PDO based DB connection'));

            return; //safegaurd
        }
        $pdo = $conn->getPdo();
        $pdo->exec("USE {$db}");
        $date = Carbon::yesterday()->setTime(22, 0)->toDateString();
        $table = "{$db}.{$db_prefix}_recentchanges";
        $sql = "INSERT INTO {$table} (rc_timestamp, rc_actor, rc_comment_id) VALUES (:timestamp, '1', '2')";
        $pdo->prepare($sql)->execute([':timestamp' => $date]);
    }
}

