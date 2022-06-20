<?php

namespace Tests\Jobs;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Jobs\KubernetesIngressDeleteJob;
use App\Wiki;
use App\WikiManager;
use Illuminate\Contracts\Queue\Job;
use Maclof\Kubernetes\Client;
use Illuminate\Support\Facades\App;

class KubernetesIngressDeleteJobTest extends TestCase
{
    use RefreshDatabase;

    public function testDoesNotDeleteNonDeletedWikis()
    {

        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory()->create( [ 'deleted_at' => null ] );
        WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->once())
            ->method('fail')
            ->with(new \RuntimeException("Wiki {$wiki->id} is not deleted, but it's ingress got called to be deleted."));

        $job = new KubernetesIngressDeleteJob($wiki->id);
        $job->setJob($mockJob);

        App::call(function ( Client $client ) use ($job) {
            $job->handle( $client );
        } );

    }

}
