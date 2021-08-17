<?php

namespace Tests\Jobs\Integration;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\WikiManager;
use App\WikiSetting;
use App\Wiki;
use App\Jobs\ElasticSearchIndexDelete;
use App\Http\Curl\CurlRequest;

/**
 * This is only meant to run when services is started with 
 * additional services from docker-compose.integration.yml
 * 
 * Delete all local indices:
 * 
 * curl -X DELETE "localhost:9200/*?pretty"
 * 
 * Example: docker-compose exec -e RUN_PHPUNIT_INTEGRATION_TEST=1 -e ELASTICSEARCH_HOST=elasticsearch.svc:9200 -T api vendor/bin/phpunit tests/Jobs/Integration/ElasticSearchIndexDeleteTest.php
 */
class ElasticSearchIndexDeleteTest extends TestCase
{
    use DatabaseTransactions;

    private $wiki;
    private $user;

    public function makeRequest( $url, $method = 'GET' ) {
        // create some dummy index
        $curlRequest = new CurlRequest();
        $curlRequest->setOptions( 
            [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_TIMEOUT => 10,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => $method,
            ]
        );
        $response = $curlRequest->execute();
        $err = $curlRequest->error();
        var_dump($response);

        if( $err ) {
            var_dump($err);
        }
        $curlRequest->close();
        
        return json_decode($response, true);
    }

    public function testDeletion()
    {
        if ( !getenv('RUN_PHPUNIT_INTEGRATION_TEST') ) {
            $this->markTestSkipped('No blazegraph instance to connect to');
        }

        if ( !getenv('ELASTICSEARCH_HOST') ) {
            throw new \Exception('ELASTICSEARCH_HOST not set');
        }

        $ELASTICSEARCH_HOST=getenv('ELASTICSEARCH_HOST');

        $response = $this->makeRequest("http://$ELASTICSEARCH_HOST/someotherdomain_general_first?pretty", 'PUT');
        $this->assertTrue($response['acknowledged']);

        // create some dummy index to delete
        $response = $this->makeRequest("http://$ELASTICSEARCH_HOST/test_domain_general_first?pretty", 'PUT');
        $this->assertTrue($response['acknowledged']);
        $response = $this->makeRequest("http://$ELASTICSEARCH_HOST/test_domain_content_first?pretty", 'PUT');
        $this->assertTrue($response['acknowledged']);

        $this->user = User::factory()->create(['verified' => true]);
        $this->wiki = Wiki::factory()->create(['domain' => 'test_domain']);
        WikiManager::factory()->create(['wiki_id' => $this->wiki->id, 'user_id' => $this->user->id]);
        WikiSetting::factory()->create(
            [
                'wiki_id' => $this->wiki->id,
                'name' => WikiSetting::wwExtEnableElasticSearch,
                'value' => false
            ]
        );

        $job = new ElasticSearchIndexDelete($this->wiki->domain, $this->wiki->id);
        $job->handle();

        // feature should get disabled
        $this->assertNull( WikiSetting::where( ['wiki_id' => $this->wiki->id, 'name' => WikiSetting::wwExtEnableElasticSearch, 'value' => true])->first());

        // first index should be gone
        $response = $this->makeRequest("http://$ELASTICSEARCH_HOST/test_domain_content_first?pretty");
        $this->assertSame($response['status'], 404);

        // second index should be gone
        $response = $this->makeRequest("http://$ELASTICSEARCH_HOST/test_domain_general_first?pretty");
        $this->assertSame($response['status'], 404);

        // the other domain should still exist
        $response = $this->makeRequest("http://$ELASTICSEARCH_HOST/someotherdomain_general_first?pretty");
        $this->assertNotNull($response['someotherdomain_general_first']);

        $this->makeRequest("http://$ELASTICSEARCH_HOST/someotherdomain_general_first?pretty", 'DELETE');

    }

}
