<?php

namespace Tests\Routes\Wiki\Managers;

use App\User;
use App\Wiki;
use App\WikiManager;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteWikiTest extends TestCase
{
    use HasFactory;
    use RefreshDatabase;

    public function testDelete()
    {
        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory('nodb')->create();
        WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        $response = $this
            ->actingAs($user, 'api')
            ->post(
                'wiki/delete',
                ['wiki' => $wiki->id, 'deletionReasons' => 'Some reason for deleting my wiki']
            );
        // check response is correct
        $response->assertStatus(200);

        $this->assertSame(
            'Some reason for deleting my wiki',
            Wiki::withTrashed()->find($wiki->id)->wiki_deletion_reason
        );
    }

    public function testFailOnWrongWikiManager(): void
    {
        $userWiki = Wiki::factory()->create();
        $otherWiki = Wiki::factory()->create();
        $user = User::factory()->create(['verified' => true]);
        WikiManager::factory()->create(['wiki_id' => $userWiki->id, 'user_id' => $user->id]);
        $this->actingAs($user, 'api')
            ->post('wiki/delete', ['wiki' => $otherWiki->id])
            ->assertStatus(401);
    }
}
