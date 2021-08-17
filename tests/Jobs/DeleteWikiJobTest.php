<?php

namespace Tests\Jobs;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Jobs\DeleteWikiDbJob;
use App\User;
use App\Wiki;
use App\WikiManager;
use App\WikiDb;
use App\Jobs\ProvisionWikiDbJob;

class DeleteWikiJobTest extends TestCase
{
    use DatabaseTransactions;

    public function testDeletesWiki()
    {
        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory()->create( [ 'deleted_at' => null ] );
        WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        $job = new ProvisionWikiDbJob('great_job', 'the_test_database', null);
        $job->handle();

        $res = WikiDb::where([
            'name' => 'the_test_database',
            'prefix' => 'great_job',
        ])->first()->update(['wiki_id' => $wiki->id]);

        $this->assertTrue( $res );
        $this->assertNotNull( WikiDb::where([ 'wiki_id' => $wiki->id ])->first() );

        $job = new DeleteWikiDbJob( $wiki->id );
        $job->handle();

        $this->assertNull( WikiDb::where([ 'wiki_id' => $wiki->id ])->first() );
    }
}
