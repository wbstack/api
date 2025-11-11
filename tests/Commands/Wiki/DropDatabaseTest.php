<?php

namespace Tests\Commands;

use App\Console\Commands\Wiki\Delete;
use App\Wiki;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DropDatabaseTest extends TestCase {
    use DatabaseTransactions;

    public function testWikiNotFound() {
        $this->artisan(
            'wbs-wiki:dropDatabase', [
                'wikiDomain' => 'imaginarywiki.wbaas.dev',
            ])
            ->assertExitCode(1)
            ->assertFailed();
    }
}
