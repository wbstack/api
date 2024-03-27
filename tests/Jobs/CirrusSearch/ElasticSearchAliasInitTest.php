<?php

namespace Tests\Jobs;

use Tests\TestCase;
use App\Http\Curl\HttpRequest;
use Illuminate\Contracts\Queue\Job;
use App\Jobs\ElasticSearchAliasInit;
use App\WikiDb;

class ElasticSearchAliasInitTest extends TestCase
{
    private $wikiId;
    private $prefix;
    private $dbName;

    public function setUp(): void {
        parent::setUp();
        $this->wikiId = 1;
        $this->prefix = 'testing_1';
        $this->dbName = WikiDb::where( 'wiki_id', $this->wikiId )->pluck( 'name' )->first();
        putenv( 'ELASTICSEARCH_SHARED_INDEX' );
    }

    private function buildAlias( string $index, string $alias ) {
        return [
            'add' => [
                'index' => $index,
                'alias' => $alias,
                'routing' => $alias,
                'filter' => [ 'prefix' => [ 'wiki' => $this->dbName . '-' ] ]
            ]
        ];
    }

    private function getMockRequest() {
        $request = $this->createMock( HttpRequest::class );
        $request->expects( $this->once() )
            ->method('setOptions')
            ->with(
                [
                    CURLOPT_URL => getenv( 'ELASTICSEARCH_HOST' ) . '/_aliases',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 60 * 15,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_HTTPHEADER => [ 'Content-Type: application/json' ],
                    CURLOPT_POSTFIELDS => json_encode( [
                        'actions' => [
                            $this->buildAlias( $this->prefix . '_content_first', $this->dbName ),
                            $this->buildAlias( $this->prefix . '_content_first', $this->dbName . '_content' ),
                            $this->buildAlias( $this->prefix . '_content_first', $this->dbName . '_content_first' ),
                            $this->buildAlias( $this->prefix . '_general_first', $this->dbName ),
                            $this->buildAlias( $this->prefix . '_general_first', $this->dbName . '_general' ),
                            $this->buildAlias( $this->prefix . '_general_first', $this->dbName . '_general_first' )
                        ]
                    ] )
                ]
            );
        return $request;
    }

    public function testSuccess()
    {
        $request = $this->getMockRequest();
        $request->method( 'execute' )
            ->willReturn( json_encode( [ 'acknowledged' => true ] ) );

        $mockJob = $this->createMock( Job::class );
        $mockJob->expects( $this->never() )
            ->method( 'fail' )
            ->withAnyParameters();

        $job = new ElasticSearchAliasInit( $this->wikiId, $this->prefix );
        $job->setJob( $mockJob );
        $job->handle( $request );
    }

    public function testFailure()
    {
        $request = $this->getMockRequest();
        $request->method( 'execute' )
            ->willReturn( json_encode( [ 'acknowledged' => false ] ) );

        $mockJob = $this->createMock( Job::class );
        $mockJob->expects( $this->once() )
                ->method( 'fail' )
                ->with( new \RuntimeException( "Updating Elasticsearch aliases failed for $this->wikiId" ) );

        $job = new ElasticSearchAliasInit( $this->wikiId, $this->prefix );
        $job->setJob( $mockJob );
        $job->handle( $request );
    }

    public function testMissingDatabaseFailure()
    {
        $this->wikiId = -1;
        $request = $this->createMock( HttpRequest::class );

        $mockJob = $this->createMock( Job::class );
        $mockJob->expects( $this->once() )
                ->method( 'fail' )
                ->with( new \RuntimeException( "Failed to get database name for $this->wikiId" ) );

        $job = new ElasticSearchAliasInit( $this->wikiId, $this->prefix );
        $job->setJob( $mockJob );
        $job->handle( $request );
    }

    public function testSuccessWithPrefixEnv()
    {
        $this->prefix = 'env_1';
        putenv( "ELASTICSEARCH_SHARED_INDEX=$this->prefix" );

        $request = $this->getMockRequest();
        $request->method( 'execute' )
            ->willReturn( json_encode( [ 'acknowledged' => true ] ) );

        $mockJob = $this->createMock( Job::class );
        $mockJob->expects( $this->never() )
            ->method( 'fail' )
            ->withAnyParameters();

        $job = new ElasticSearchAliasInit( $this->wikiId );
        $job->setJob( $mockJob );
        $job->handle( $request );
    }

    public function testMissingPrefixFailure()
    {
        $request = $this->createMock( HttpRequest::class );

        $mockJob = $this->createMock( Job::class );
        $mockJob->expects( $this->once() )
                ->method( 'fail' )
                ->with( new \RuntimeException( "Missing shared index prefix for $this->wikiId" ) );

        $job = new ElasticSearchAliasInit( $this->wikiId );
        $job->setJob( $mockJob );
        $job->handle( $request );
    }
}
