<?php

namespace Tests;

use App\KnowledgeEquityResponse;
use App\Wiki;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Str;

class KnowledgeEquityResponseTest extends TestCase {
    use RefreshDatabase;

    protected Wiki $wiki;

    protected function setUp(): void {
        parent::setUp();
        $this->wiki = Wiki::factory()->create();
    }

    public function testCreateValidKnowledgeEquityResponse(): void {
        $knowledgeEquityResponse = new KnowledgeEquityResponse([
            'wiki_id' => $this->wiki->id,
            'selectedOption' => 'yes',
            'freeTextResponse' => 'Because it just does',
        ]);

        $knowledgeEquityResponse->save();

        $this->assertDatabaseHas('knowledge_equity_responses', [
            'wiki_id' => $this->wiki->id,
            'selectedOption' => 'yes',
            'freeTextResponse' => 'Because it just does',
        ]);
    }

    public function testCreateValidKnowledgeEquityResponseNoFreeText(): void {
        $knowledgeEquityResponse = new KnowledgeEquityResponse([
            'wiki_id' => $this->wiki->id,
            'selectedOption' => 'yes',
        ]);

        $knowledgeEquityResponse->save();

        $this->assertDatabaseHas('knowledge_equity_responses', [
            'wiki_id' => $this->wiki->id,
            'selectedOption' => 'yes',
        ]);
    }

    public function testCreateValidKnowledgeEquityResponse3000CharFreeText(): void {
        $longFreeText = Str::random(3000);
        $knowledgeEquityResponse = new KnowledgeEquityResponse([
            'wiki_id' => $this->wiki->id,
            'selectedOption' => 'yes',
            'freeTextResponse' => $longFreeText,
        ]);

        $knowledgeEquityResponse->save();

        $this->assertDatabaseHas('knowledge_equity_responses', [
            'wiki_id' => $this->wiki->id,
            'selectedOption' => 'yes',
        ]);
    }

    public function testDeleteValidKnowledgeEquityResponsePreventsWikiDeletion(): void {
        $knowledgeEquityResponse = new KnowledgeEquityResponse([
            'wiki_id' => $this->wiki->id,
            'selectedOption' => 'yes',
        ]);

        $knowledgeEquityResponse->save();

        $this->wiki->delete();
        $this->assertThrows(function () {
            $this->wiki->forceDelete();
        }, QueryException::class);
    }
}
