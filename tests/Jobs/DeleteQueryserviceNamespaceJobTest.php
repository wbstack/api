<?php

namespace Tests\Jobs;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Http\Curl\HttpRequest;
use App\Jobs\DeleteQueryserviceNamespaceJob;
use App\QueryserviceNamespace;
use Illuminate\Support\Facades\DB;
use App\WikiManager;
use App\User;
use App\Wiki;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\Job;

class DeleteQueryserviceNamespaceJobTest extends TestCase
{
    use DatabaseTransactions;

    public function testDeleteNamespace()
    {
        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory()->create( [ 'deleted_at' => Carbon::now()->subDays(30)->timestamp ] );
        WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        $namespace = 'asdf';
        $host = config('app.queryservice_host');

        $dbRow = QueryserviceNamespace::create([
            'namespace' => $namespace,
            'backend' => $host,
        ]);

        DB::table('queryservice_namespaces')->where(['id'=>$dbRow->id])->limit(1)->update(['wiki_id' => $wiki->id]);

        $mockResponse = 'DELETED: '.$namespace;
        $request = $this->createMock(HttpRequest::class);
        $request->expects($this->exactly(1))
            ->method('execute')
            ->willReturn( $mockResponse );

        $request->expects($this->exactly(1))
            ->method('setOptions')
            ->with( 
            [ 
                CURLOPT_URL => $host.'/bigdata/namespace/' . $namespace,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_TIMEOUT => 10,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                // User agent is needed by the query service...
                CURLOPT_USERAGENT => 'WBStack DeleteQueryserviceNamespaceJob',
                CURLOPT_CUSTOMREQUEST => 'DELETE',
                CURLOPT_HTTPHEADER => [
                    'content-type: text/plain',
                ]
            ]);

        
        $job = new DeleteQueryserviceNamespaceJob($wiki->id, $request);
        $job->handle();

        $this->assertSame(
             0, 
             QueryserviceNamespace::where( ['namespace' => $namespace ])->count()
        );
    }

    public function testNoWiki() {
        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->once())
        ->method('fail')
        ->with(new \RuntimeException("Namespace for wiki 123 not found."));

        $request = $this->createMock(HttpRequest::class);
        $request->expects($this->never())
            ->method('execute');

        $job = new DeleteQueryserviceNamespaceJob(123, $request);
        $job->setJob($mockJob);
        $job->handle();
    }


    public function testNoNamespace() {

        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory()->create( [ 'deleted_at' => Carbon::now()->subDays(30)->timestamp ] );
        WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->once())
        ->method('fail')
        ->with(new \RuntimeException("Namespace for wiki {$wiki->id} not found."));

        $request = $this->createMock(HttpRequest::class);
        $request->expects($this->never())
            ->method('execute');

        $job = new DeleteQueryserviceNamespaceJob($wiki->id, $request);
        $job->setJob($mockJob);
        $job->handle();
    }

}