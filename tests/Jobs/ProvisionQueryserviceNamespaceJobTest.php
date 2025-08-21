<?php

namespace Tests\Jobs;

use App\Http\Curl\HttpRequest;
use App\Jobs\ProvisionQueryserviceNamespaceJob;
use App\QueryserviceNamespace;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProvisionQueryserviceNamespaceJobTest extends TestCase {
    use DatabaseTransactions;

    public function testCreatesNamespace() {
        $namespace = 'asdf';

        $mockResponse = 'CREATED: ' . $namespace;

        $request = $this->createMock(HttpRequest::class);
        $request->expects($this->exactly(1))
            ->method('execute')
            ->willReturn($mockResponse);

        $properties = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '../../app/data/RWStore.properties');
        $properties = str_replace('REPLACE_NAMESPACE', $namespace, $properties);

        $request->expects($this->exactly(1))
            ->method('setOptions')
            ->with(
                [
                    CURLOPT_URL => config('app.queryservice_host') . '/bigdata/namespace',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_TIMEOUT => 10,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    // User agent is needed by the query service...
                    CURLOPT_USERAGENT => 'WBStack ProvisionQueryserviceNamespaceJob',
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => $properties,
                    CURLOPT_HTTPHEADER => [
                        'content-type: text/plain',
                    ],
                ]);

        $job = new ProvisionQueryserviceNamespaceJob($namespace, null);
        $job->handle($request);

        $this->assertSame(
            1,
            QueryserviceNamespace::where(['namespace' => $namespace])->count()
        );
    }

    public function testMaxFree() {
        $namespace = 'asdf';

        $request = $this->createMock(HttpRequest::class);
        $request->expects($this->never())
            ->method('execute');

        $request->expects($this->never())
            ->method('setOptions');

        QueryserviceNamespace::where([
            'wiki_id' => null,
        ])->delete();

        QueryserviceNamespace::create([
            'namespace' => 'derp',
            'backend' => 'asdf',
        ]);
        QueryserviceNamespace::create([
            'namespace' => 'derp2',
            'backend' => 'asdf2',
        ]);

        $job = new ProvisionQueryserviceNamespaceJob($namespace, 1);
        $job->handle($request);

        $this->assertSame(
            0,
            QueryserviceNamespace::where(['namespace' => $namespace])->count()
        );
    }
}
