<?php

namespace Tests\Routes\Auth;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\WikiManager;
use App\Wiki;
use App\WikiSetting;
use Carbon\Carbon;
use App\Jobs\DeleteWikiFinalizeJob;
use App\WikiDb;
use Illuminate\Support\Facades\Storage;
use App\Http\Curl\HttpRequest;
use Illuminate\Contracts\Queue\Job;

class DeleteWikiFinalizeJobTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void {
        parent::setUp();
        Storage::fake('gcs-public-static');
    }

    public function testDeleteWiki()
    {
        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory()->create( [ 'deleted_at' => Carbon::now()->timestamp ] );
        $manager = WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);
        $setting = WikiSetting::create(['wiki_id' => $wiki->id, 'name' => 'asdf', 'value' => false]);

        $request = $this->createMock(HttpRequest::class);
        $request->expects($this->never())->method('execute');

        $job = new DeleteWikiFinalizeJob( $wiki->id );
        $job->handle($request);

        $this->assertNull( Wiki::withTrashed()->where('id', $wiki->id)->first() );
        $this->assertNull( WikiManager::whereId($manager->id)->first() );
        $this->assertNull( WikiSetting::whereId($setting->id)->first() );
    }

    public function testDoesNotDeleteWhenResourcesStillExist()
    {
        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory()->create( [ 'deleted_at' => Carbon::now()->timestamp ] );
        $manager = WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);
        $setting = WikiSetting::create(['wiki_id' => $wiki->id, 'name' => 'asdf', 'value' => false]);
        $wikiDbName = 'wikiDbName';
        $wikiDB = WikiDb::create([
            'name' => $wikiDbName,
            'user' => 'asdasd',
            'password' => 'asdasfasfasf',
            'version' => 'asdasdasdas',
            'prefix' => 'asdasd',
            'wiki_id' => $wiki->id
        ]);

        $mockResponse = "index\n" . "{$wikiDbName}_content_blabla\n" . "{$wikiDbName}_general_bla\n";
        $request = $this->createMock(HttpRequest::class);
        $request->expects($this->once())
            ->method('execute')
            ->willReturn($mockResponse);

        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->once())->method('fail')->with(
            new \RuntimeException("Elasticsearch indices with basename {$wikiDbName} still exists in http://localhost:9200")
        );

        $job = new DeleteWikiFinalizeJob( $wiki->id );
        $job->setJob($mockJob);
        $job->handle($request);

        // TODO should this dispatch the deletion job if some resources still existed?

        // should not delete because WikiDB job probably failed and couldn't delete
        $this->assertNotNull( Wiki::withTrashed()->where('id', $wiki->id)->first() );
        $this->assertNotNull( WikiManager::whereId($manager->id)->first() );
        $this->assertNotNull( WikiSetting::whereId($setting->id)->first() );
        $this->assertNotNull( WikiDb::whereId($wikiDB->id)->first() );

    }

    public function testDoesNotDeleteNonDeletedWikis()
    {
        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory()->create( [ 'deleted_at' => null ] );
        $manager = WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);
        $setting = WikiSetting::create(['wiki_id' => $wiki->id, 'name' => 'asdf', 'value' => false]);

        $request = $this->createMock(HttpRequest::class);
        $request->expects($this->never())->method('execute');

        $job = new DeleteWikiFinalizeJob( $wiki->id );
        $job->handle($request);

        // should not get deleted when deleted_at is not set to something.
        $this->assertNotNull( Wiki::withTrashed()->where('id', $wiki->id)->first() );
        $this->assertNotNull( WikiManager::whereId($manager->id)->first() );
        $this->assertNotNull( WikiSetting::whereId($setting->id)->first() );
    }

    public function testDeletesFiles()
    {

        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory()->create( [ 'deleted_at' => Carbon::now()->timestamp ] );
        $manager = WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);
        $setting = WikiSetting::create(['wiki_id' => $wiki->id, 'name' => WikiSetting::wgFavicon, 'value' => false]);

        $siteDir = Wiki::getSiteDirectory($wiki->id);

        Storage::disk('gcs-public-static')
            ->makeDirectory($siteDir);

        Storage::disk('gcs-public-static')->assertExists($siteDir);

        $request = $this->createMock(HttpRequest::class);
        $request->expects($this->never())->method('execute');

        $job = new DeleteWikiFinalizeJob( $wiki->id );
        $job->handle($request);

        // deletion happened
        $this->assertNull( Wiki::withTrashed()->where('id', $wiki->id)->first() );
        $this->assertNull( WikiManager::whereId($manager->id)->first() );
        $this->assertNull( WikiSetting::whereId($setting->id)->first() );

        // site dir gone
        Storage::disk('gcs-public-static')->assertMissing($siteDir);
    }
}
