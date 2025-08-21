<?php

namespace Tests\Jobs\Integration;

use App\Http\Curl\CurlRequest;
use App\Jobs\ElasticSearchIndexDelete;
use App\User;
use App\Wiki;
use App\WikiDb;
use App\WikiManager;
use App\WikiSetting;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

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
class ElasticSearchIndexDeleteTest extends TestCase {
    use DatabaseTransactions;
    use DispatchesJobs;

    private $wiki;

    private $user;

    public function makeRequest($url, $method = 'GET') {
        // create some dummy index
        $curlRequest = new CurlRequest;
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

        if ($err) {
            var_dump($err);
        }
        $curlRequest->close();

        return json_decode($response, true);
    }

    public function testDeletion() {
        if (! getenv('RUN_PHPUNIT_INTEGRATION_TEST')) {
            $this->markTestSkipped('No blazegraph instance to connect to');
        }

        $ELASTICSEARCH_HOST = data_get(Config::get('wbstack.elasticsearch_hosts'), 0);

        if (! $ELASTICSEARCH_HOST) {
            throw new \Exception('ELASTICSEARCH_HOST / wbstack.elasticsearch_hosts not set');
        }

        $response = $this->makeRequest("http://$ELASTICSEARCH_HOST/someotherdbname_general_first?pretty", 'PUT');
        $this->assertTrue($response['acknowledged']);

        // create some dummy index to delete
        $response = $this->makeRequest("http://$ELASTICSEARCH_HOST/test_db_name_general_first?pretty", 'PUT');
        $this->assertTrue($response['acknowledged']);
        $response = $this->makeRequest("http://$ELASTICSEARCH_HOST/test_db_name_content_first?pretty", 'PUT');
        $this->assertTrue($response['acknowledged']);

        $this->user = User::factory()->create(['verified' => true]);
        $this->wiki = Wiki::factory()->create();
        WikiManager::factory()->create(['wiki_id' => $this->wiki->id, 'user_id' => $this->user->id]);
        WikiSetting::factory()->create(
            [
                'wiki_id' => $this->wiki->id,
                'name' => WikiSetting::wwExtEnableElasticSearch,
                'value' => false,
            ]
        );
        WikiDb::factory()->create([
            'wiki_id' => $this->wiki->id,
            'name' => 'test_db_name',
        ]);

        $this->wiki->delete();

        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->never())->method('fail');
        $job = new ElasticSearchIndexDelete($this->wiki->id);
        $job->setJob($mockJob);
        $this->dispatchSync($job);

        // feature should get disabled
        $this->assertNull(WikiSetting::where(['wiki_id' => $this->wiki->id, 'name' => WikiSetting::wwExtEnableElasticSearch, 'value' => true])->first());

        // first index should be gone
        $response = $this->makeRequest("http://$ELASTICSEARCH_HOST/test_db_name_content_first?pretty");
        $this->assertSame($response['status'], 404);

        // second index should be gone
        $response = $this->makeRequest("http://$ELASTICSEARCH_HOST/test_db_name_general_first?pretty");
        $this->assertSame($response['status'], 404);

        // the other domain should still exist
        $response = $this->makeRequest("http://$ELASTICSEARCH_HOST/someotherdbname_general_first?pretty");
        $this->assertNotNull($response['someotherdbname_general_first']);

        $this->makeRequest("http://$ELASTICSEARCH_HOST/someotherdbname_general_first?pretty", 'DELETE');

    }
}
