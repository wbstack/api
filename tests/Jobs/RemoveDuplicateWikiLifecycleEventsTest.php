<?php

namespace Jobs;

use App\Jobs\RemoveDuplicateWikiLifecycleEvents;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemoveDuplicateWikiLifecycleEventsTest extends TestCase
{
    use RefreshDatabase;

    public function testRemovesDuplicateEvents(): void
    {
        # create two wikis
        # one with just one WikiLifecycleEvents object
        # another with *two*
        
        # ensure the job doesn't decide it's failed
        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->never())->method('fail');

        $job = new RemoveDuplicateWikiLifecycleEvents();
        $job->setJob($mockJob);
        $job->handle();

    }

}
