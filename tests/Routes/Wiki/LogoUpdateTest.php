<?php

namespace Tests\Routes\Wiki\Managers;

use App\User;
use App\Wiki;
use App\WikiManager;
use App\WikiSetting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Tests\TestCase;

class LogoUpdateTest extends TestCase {
    use DatabaseTransactions;
    use HasFactory;

    public function testSvgUpload(): void {
        $storage = Storage::fake('static-assets');
        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory('nodb')->create();
        WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);
        $file = UploadedFile::fake()->createWithContent('logo.svg', file_get_contents(__DIR__ . '/../../data/logo.svg'));

        $this->actingAs($user, 'api')
            ->postJson('wiki/logo/update', ['wiki' => $wiki->id, 'logo' => $file])
            ->assertOk()
            ->assertJson(['success' => true, 'url' => $wiki->settings()->firstWhere('name', WikiSetting::wgLogo)->value]);

        $this->assertStringContainsString('/logo.svg?u=', $wiki->settings()->firstWhere('name', WikiSetting::wwLogoSvg)->value);
    }

    /**
     * @dataProvider invalidLogoProvider
     */
    public function testInvalidUploadDoesNotChangeExistingLogo(string $name, string $contents): void {
        $storage = Storage::fake('static-assets');
        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory('nodb')->create();
        WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);
        WikiSetting::create(['wiki_id' => $wiki->id, 'name' => WikiSetting::wgLogo, 'value' => 'existing.png']);
        $file = UploadedFile::fake()->createWithContent($name, $contents);

        $this->actingAs($user, 'api')
            ->postJson('wiki/logo/update', ['wiki' => $wiki->id, 'logo' => $file])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('logo');
        $this->assertSame('existing.png', $wiki->settings()->firstWhere('name', WikiSetting::wgLogo)->value);
        $this->assertSame([], $storage->allFiles());
    }

    public static function invalidLogoProvider(): iterable {
        yield 'wrong format' => ['logo.svg', '<html>not SVG</html>'];
        yield 'malformed SVG' => ['logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><g></svg>'];
        yield 'no drawable size' => ['logo.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="0" height="0"/>'];
        yield 'oversized file' => ['logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><!--' . str_repeat('x', 2048 * 1024) . '--></svg>'];
        yield 'external reference' => ['logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><image href="/etc/passwd"/></svg>'];
    }

    public function testUpdate() {
        $storage = Storage::fake('static-assets');
        $file = UploadedFile::fake()->createWithContent('logo_200x200.png', file_get_contents(__DIR__ . '/../../data/logo_200x200.png'));

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

    public function testFailOnWrongWikiManager(): void {
        $userWiki = Wiki::factory()->create();
        $otherWiki = Wiki::factory()->create();
        $user = User::factory()->create(['verified' => true]);
        WikiManager::factory()->create(['wiki_id' => $userWiki->id, 'user_id' => $user->id]);
        $file = UploadedFile::fake()
            ->createWithContent('logo_200x200.png', file_get_contents(__DIR__ . '/../../data/logo_200x200.png'));
        $this->actingAs($user, 'api')
            ->post('wiki/logo/update', ['wiki' => $otherWiki->id, 'logo' => $file])
            ->assertStatus(403);
    }
}
