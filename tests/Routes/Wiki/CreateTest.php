<?php

namespace Tests\Routes\Wiki\Managers;

use Tests\Routes\Traits\OptionsRequestAllowed;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\User;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ElasticSearchIndexInit;
use App\Jobs\ProvisionWikiDbJob;
use App\Jobs\MediawikiInit;
use App\WikiSetting;

class CreateTest extends TestCase
{
    protected $route = 'wiki/create';

    use OptionsRequestAllowed;
    use DatabaseTransactions;

    public function testWikiCreateDispatchesSomeJobs()
    {
        Queue::fake();

        $user = User::factory()->create(['verified' => true]);
        Queue::assertNothingPushed();

        $response = $this->actingAs($user, 'api')
        ->json(
            'POST', 
            $this->route, 
            [
                'domain' => 'derp.com',
                'sitename' => 'merp',
                'username' => 'AdminBoss'
            ]
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.domain', 'derp.com')
            ->assertJsonPath('data.name', null)
            ->assertJsonPath('success', true );

        Queue::assertPushed( ProvisionWikiDbJob::class, 1);
        Queue::assertPushed( MediawikiInit::class, 1);
        Queue::assertPushed( ElasticSearchIndexInit::class, 1);
        
        $id = $response->original['data']['id'];

        $this->assertSame(
            1, 
            WikiSetting::where( [ 'name' => WikiSetting::wgSecretKey, 'wiki_id' => $id ] )->count()
        );

        $this->assertSame(
            1, 
            WikiSetting::where( [ 'name' => WikiSetting::wwExtEnableElasticSearch, 'value' => true, 'wiki_id' => $id ] )->count()
        );


    }
}
