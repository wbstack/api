<?php

namespace Tests\Commands;

use App\Console\Commands\Wiki\Delete;
use App\Wiki;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use DatabaseTransactions;

    public function testDeleteWikiBySiteName()
    {
        $wikiName = 'potatoWiki';
        $wiki = Wiki::factory(['sitename' => $wikiName])->create();

        $this->artisan('wbs-wiki:delete', [
            'key' => 'sitename',
            'value' => $wikiName,
        ])
            ->expectsOutput(Delete::SUCCESS);

        $this->assertSoftDeleted($wiki);
    }

    public function testDeleteWikiByDomain()
    {
        $domain = 'deleted.wiki.org';
        $wiki = Wiki::factory(['domain' => $domain])->create();

        $this->artisan('wbs-wiki:delete', [
            'key' => 'domain',
            'value' => $domain,
        ]);

        $this->assertSoftDeleted($wiki);
    }

    public function testGivenWikiDoesNotExist_commandFails()
    {
        $this->artisan(
            'wbs-wiki:delete', [
            'key' => 'sitename',
            'value' => 'iDontExist',
        ])
            ->expectsOutput(Delete::ERR_WIKI_DOES_NOT_EXIST)
            ->assertFailed();
    }

    public function testGivenKeyValuePairMatchesMultipleWikis_commandFails()
    {
        $name = 'potatoWiki';
        $wiki1 = Wiki::factory(['sitename' => $name])->create();
        $wiki2 = Wiki::factory(['sitename' => $name])->create();

        $this->artisan(
            'wbs-wiki:delete', [
            'key' => 'sitename',
            'value' => $name,
        ])
            ->expectsOutput(Delete::ERR_AMBIGUOUS_KEY_VALUE)
            ->assertFailed();

        $this->assertNotSoftDeleted($wiki1);
        $this->assertNotSoftDeleted($wiki2);
    }

}
