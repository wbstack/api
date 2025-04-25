<?php

namespace Tests;

use Tests\TestCase;
use App\WikiDailyMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WikiDailyMetricsTest extends TestCase
{
    use RefreshDatabase;

    public static function areMetricsEqualProvider(){
        yield 'is the same' => [
            new WikiDailyMetrics(["pages" => 1, "is_deleted" => false ]),
            new WikiDailyMetrics(["pages" => 1, "is_deleted" => false ]),
            true
        ];

        yield 'more pages' => [
            new WikiDailyMetrics(["pages" => 1, "is_deleted" => false ]),
            new WikiDailyMetrics(["pages" => 20, "is_deleted" => false ]),
            false
        ];

        yield 'less pages' => [
            new WikiDailyMetrics(["pages" => 10, "is_deleted" => false ]),
            new WikiDailyMetrics(["pages" => 1, "is_deleted" => false ]),
            false
        ];

        yield 'is deleted' => [
            new WikiDailyMetrics(["pages" => 1, "is_deleted" => false ]),
            new WikiDailyMetrics(["pages" => 1, "is_deleted" => true ]),
            false
        ];
    }

    /**
     * @dataProvider areMetricsEqualProvider
     */
    public function testAreMetricsEqual(
        WikiDailyMetrics $wikiDailyMetrics1,
        WikiDailyMetrics $wikiDailyMetrics2,
        $assertion
    ): void
    {
        $this->assertEquals(
            $wikiDailyMetrics1->areMetricsEqual($wikiDailyMetrics2),
            $assertion
        );
    }
}
