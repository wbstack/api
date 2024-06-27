<?php

namespace Tests\Routes\Wiki\Managers;

use App\User;
use App\Wiki;
use App\WikiManager;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Tests\TestCase;

class DeleteWikiTest extends TestCase
{
    use HasFactory;

    public function testDelete()
    {

        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory('nodb')->create();
        WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        $response = $this
            ->actingAs($user, 'api')
            ->post(
                'wiki/delete',
                ['wiki' => $wiki->id, 'deletionReason' => 'Some reason for deleting my wiki']
            );
        // check response is correct
        $response->assertStatus(200);
        $this->assertSame('Some reason for deleting my wiki', $response->original);
    }
}
