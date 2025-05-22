<?php

namespace Tests\Routes\Wiki\Managers;

use App\User;
use App\Wiki;
use App\WikiManager;
use App\WikiSetting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Tests\TestCase;

class LogoUpdateTest extends TestCase
{
    use HasFactory;

    public function testUpdate()
    {
        $storage = Storage::fake('static-assets');
        $file = UploadedFile::fake()->createWithContent("logo_200x200.png", file_get_contents(__DIR__ . "/../../data/logo_200x200.png"));

        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory('nodb')->create();
        WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        $response = $this
            ->actingAs($user, 'api')
            ->post(
                'wiki/logo/update',
                ['wiki' => $wiki->id, 'logo' => $file]
            );

        $expectedRawPath = Wiki::getLogosDirectory($wiki->id) . '/raw.png';
        $expectedLogoPath = Wiki::getLogosDirectory($wiki->id) . '/135.png';
        $expectedFaviconPath = Wiki::getLogosDirectory($wiki->id) . '/64.ico';
        $expectedLogoURL = $wiki->settings()->firstWhere(['name' => WikiSetting::wgLogo])->value;

        // check response is correct
        $response->assertStatus(200);
        $response->assertJson(['url' => $expectedLogoURL]);

        // check raw logo uploaded
        $storage->assertExists($expectedRawPath);

        // check logo resized to 135
        $logo = Image::make($storage->path($expectedLogoPath));
        $this->assertSame(135, $logo->height());
        $this->assertSame(135, $logo->width());

        // check favicon resized to 64
        $logo = Image::make($storage->path($expectedFaviconPath));
        $this->assertSame(64, $logo->height());
        $this->assertSame(64, $logo->width());
    }

    public function testFailOnWrongWikiManager(): void
    {
        $userWiki = Wiki::factory()->create();
        $otherWiki = Wiki::factory()->create();
        $user = User::factory()->create(['verified' => true]);
        WikiManager::factory()->create(['wiki_id' => $userWiki->id, 'user_id' => $user->id]);
        $file = UploadedFile::fake()
            ->createWithContent("logo_200x200.png", file_get_contents(__DIR__ . "/../../data/logo_200x200.png"));
        $this->actingAs($user, 'api')
            ->post('wiki/logo/update', ['wiki' => $otherWiki->id, 'logo' => $file])
            ->assertStatus(401);
    }
}
