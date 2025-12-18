<?php

namespace Tests\Jobs;

use App\Jobs\ProvisionWikiDbJob;
use App\WikiDb;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProvisionWikiDbJobTest extends TestCase {
    use DatabaseTransactions;

    private function createWikiDb($name, $version, $wikiId) {
        return WikiDb::create([
            'name' => $name,
            'version' => $version,
            'wiki_id' => $wikiId,
        ]);
    }

    public static function amountProvider() {
        yield 'run 1 time, max 0' => [
            0,
            0,
        ];

        yield 'run 1 time, max 1' => [
            1,
            1,
        ];

        yield 'run 2 time, max 1' => [
            2,
            1,
        ];

        yield 'run 2 time, max 2' => [
            2,
            2,
        ];

        yield 'run 10 time, max 10' => [
            10,
            10,
        ];

        yield 'run 10 time, max 1' => [
            10,
            1,
        ];

        yield 'run 20 time, max 10' => [
            20,
            10,
        ];
    }

    /**
     * @dataProvider amountProvider
     */
    public function testRun($times, $max) {
        $this->assertSame(
            0,
            WikiDb::where('wiki_id', null)->count(),
        );

        $manager = $this->app->make('db');

        for ($i = 0; $i < $times; $i++) {
            $job = new ProvisionWikiDbJob(null, null, $max);
            $job->handle($manager);
        }

        $this->assertSame(
            $max,
            WikiDb::where('wiki_id', null)->count(),
        );
    }
}
