<?php

namespace Tests\Jobs;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\QueryserviceNamespace;
use Illuminate\Contracts\Queue\Job;
use App\Jobs\DeleteWikiDispatcherJob;
use App\User;
use App\Wiki;
use App\WikiManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\Bus;
use App\Jobs\KubernetesIngressDeleteJob;
use App\Jobs\DeleteWikiDbJob;
use App\Jobs\DeleteWikiFinalizeJob;
use App\WikiSetting;
use App\Jobs\ElasticSearchIndexDelete;
use App\Jobs\DeleteQueryserviceNamespaceJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use TiMacDonald\Log\LogFake;
use Illuminate\Support\Str;
use App\Jobs\ProvisionWikiDbJob;
use App\WikiDb;
use Illuminate\Support\Facades\Storage;

class DeleteWikiDispatcherJobTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void {
        parent::setUp();
        Log::swap(new LogFake);
        Storage::fake('gcs-public-static');
        $this->wiki = $this->getWiki();
    }

    private function getWiki( $daysSinceDelete = 30 ): Wiki {
        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory()->create( [ 'deleted_at' => $daysSinceDelete > 0 ? Carbon::now()->subDays($daysSinceDelete)->timestamp : null ] );
        WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);
        return $wiki;
    }

    public function testDeleteDispatcher()
    {
        Bus::fake();

        $mockJob = $this->createMock(Job::class);
        $job = new DeleteWikiDispatcherJob();
        $job->setJob($mockJob);
        $job->handle();

        Bus::assertChained([
            new KubernetesIngressDeleteJob( $this->wiki->id ),
            new DeleteWikiDbJob($this->wiki->id),
            new DeleteWikiFinalizeJob($this->wiki->id)
        ]);
    }

    public function testNothingDispatchesUntilItsTime() {
        Bus::fake();

        // to be deleted already gone
        $this->wiki->delete();

        $existingWikiNotDelete = $this->getWiki(-1);
        $existingWikiNotTimeYet = $this->getWiki(29);

        $mockJob = $this->createMock(Job::class);
        $job = new DeleteWikiDispatcherJob();
        $job->setJob($mockJob);
        $job->handle();

        Log::assertLogged('info', function ($message, $context) {
            return Str::contains($message, 'Found no soft deleted wikis. exiting.');
        });

        Bus::assertNotDispatched(KubernetesIngressDeleteJob::class);
        Bus::assertNotDispatched(DeleteWikiDbJob::class);
        Bus::assertNotDispatched(DeleteQueryserviceNamespaceJob::class);
        Bus::assertNotDispatched(ElasticSearchIndexDelete::class);
        Bus::assertNotDispatched(DeleteWikiFinalizeJob::class);

    }

    public function testDeleteWithOptionalResources()
    {
        Bus::fake();

        $namespace = QueryserviceNamespace::create(
            [
                'namespace' => 'derp',
                'backend' => 'interwebs'
            ]
        );

        $nsAssignment = DB::table('queryservice_namespaces')->where(['id'=>$namespace->id])->limit(1)->update(['wiki_id' => $this->wiki->id]);
        $this->assertNotNull($nsAssignment);

        WikiSetting::factory()->create(
            [ 
                'wiki_id' => $this->wiki->id,
                'name' => WikiSetting::wwExtEnableElasticSearch,
                'value' => true 
            ]
        );

        $mockJob = $this->createMock(Job::class);
        $job = new DeleteWikiDispatcherJob();
        $job->setJob($mockJob);
        $job->handle();

        Log::assertLogged('info', function ($message, $context) {
            return Str::contains($message, "Dispatching hard delete job chain for id: {$this->wiki->id}");
        });

        Bus::assertChained([
            new DeleteQueryserviceNamespaceJob( $namespace->id ),
            new ElasticSearchIndexDelete( $this->wiki->id ),
            new KubernetesIngressDeleteJob( $this->wiki->id ),
            new DeleteWikiDbJob($this->wiki->id),
            new DeleteWikiFinalizeJob($this->wiki->id)
        ]);
    }

    public function testActuallyRunningJobsThatDelete()
    {
        $this->wiki->update(['domain' => 'asdasdaf.wiki.opencura.com']);
        
        // create db to be deleted
        $job = new ProvisionWikiDbJob('great_job', 'the_test_database', null);
        $job->handle( $this->app->make('db') );

        $res = WikiDb::where([
            'name' => 'the_test_database',
            'prefix' => 'great_job',
        ])->first()->update(['wiki_id' => $this->wiki->id]);

        $this->assertTrue( $res );
        $this->assertNotNull( WikiDb::where([ 'wiki_id' => $this->wiki->id ])->first() );
        
        $mockJob = $this->createMock(Job::class);
        $job = new DeleteWikiDispatcherJob();
        $job->setJob($mockJob);
        $job->handle();

        $this->assertNull( WikiSetting::whereWikiId($this->wiki->id)->first() );
        $this->assertNull( WikiManager::whereWikiId($this->wiki->id)->first() );
        $this->assertNull( Wiki::whereId($this->wiki->id)->first() );
        $this->assertNull( WikiDb::where([ 'wiki_id' => $this->wiki->id ])->first() );

        Log::assertLogged('info', function ($message, $context) {
            return Str::contains($message, "Dispatching hard delete job chain for id: {$this->wiki->id}");
        });

        $mockJob->expects($this->never())->method('fail');
    }

}