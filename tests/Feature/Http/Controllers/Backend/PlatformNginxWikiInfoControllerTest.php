<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Backend;

use App\Enums\ReviewSubmissionActionType;
use App\Enums\UserRole;
use App\ReviewSubmission;
use App\Wiki;
use App\WikiDb;
use App\WikiSuspension;
use Generator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PlatformNginxWikiInfoControllerTest extends TestCase {
    use DatabaseTransactions;

    protected string $route = '/backend/wiki/platformNginxInfo';

    protected function setUp(): void {
        parent::setUp();

        Config::set('mw-db-version-map', [
            'mw1.39-wbs1' => '139',
            'mw1.43-wbs2' => '143',
        ]);
    }

    public function testSuccess() {
        $expectedHosts = [
            'alpha' => 'mediawiki-143-app-alpha.default.svc.cluster.local',
            'api' => 'mediawiki-143-app-api.default.svc.cluster.local',
            'backend' => 'mediawiki-143-app-backend.default.svc.cluster.local',
            'web' => 'mediawiki-143-app-web.default.svc.cluster.local',
        ];

        $this->createWiki('test139.wikibase.cloud', 'mw1.39-wbs1');
        $this->createWiki('test143.wikibase.cloud', 'mw1.43-wbs2');

        $this->get("$this->route?domain=test143.wikibase.cloud")
            ->assertStatus(200)
            ->assertContent('OK')
            ->assertHeader('x-mw-alpha-host', $expectedHosts['alpha'])
            ->assertHeader('x-mw-api-host', $expectedHosts['api'])
            ->assertHeader('x-mw-backend-host', $expectedHosts['backend'])
            ->assertHeader('x-mw-web-host', $expectedHosts['web']);
    }

    public function testDomainNotfound() {
        $this->get("$this->route?domain=notfound.wikibase.cloud")
            ->assertStatus(404);
    }

    public function testUnknownDbVersion() {
        $this->createWiki('test.wikibase.cloud', 'unknownVersion');

        $this->get("$this->route?domain=test.wikibase.cloud")
            ->assertStatus(500);
    }

    private function createWiki(string $domain, string $version) {
        Wiki::factory(['domain' => $domain])
            ->has(WikiDb::factory(['version' => $version]))
            ->create();
    }

    // FUTURE: Do we write each individual test case or write some for loops?
    // FUTURE: Improve readability of tests/providers (e.g. Create helper methods? Name parameters in provider?
    //         Shorted data set names? Calculate days relative to now()? Create builder class for constructing test cases?)
    /**
     * @covers PlatformNginxWikiInfoController::getInfoForDomain
     */
    public static function providePlatformNginxWikiInfoControllerCases(): Generator {
        yield 'no active review committee decision, expiry date in the future, submission submitted => 200 OK w/ Host Headers' => [
            '2026-11-01',
            ['active_from' => '2027-02-15'],
            ['created_at' => '2026-11-20'],
            [
                'type' => ReviewSubmissionActionType::SUBMITTED,
                'actor_user_role' => UserRole::WIKI_MANAGER,
                'created_at' => '2026-11-20',
            ],
            true,
        ];

        yield 'no active review committee decision, expiry date in the future, submission review started => 200 OK w/ Host Headers' => [
            '2026-11-01',
            ['active_from' => '2027-02-15'],
            ['created_at' => '2026-11-20'],
            [
                // TODO: we should really create the previous Actions first
                'type' => ReviewSubmissionActionType::REVIEW_STARTED,
                'actor_user_role' => UserRole::WIKI_MANAGER,
                'created_at' => '2026-11-20',
            ],
            true,
        ];

        yield 'no active review committee decision, expiry date in the future, no submission => 200 OK w/ Host Headers' => [
            '2026-11-01',
            ['active_from' => '2027-02-15'],
            null,
            null,
            true,
        ];

        yield 'no active review committee decision, expiry date in the future, submission approved => 200 OK w/ Host Headers' => [
            '2026-11-01',
            ['active_from' => '2027-02-15'],
            // TODO: don't make submission create_at and action created_at the same datetime
            ['created_at' => '2026-11-20'],
            [
                // TODO: we should really create the previous Actions first
                'type' => ReviewSubmissionActionType::APPROVED,
                'actor_user_role' => UserRole::WIKI_MANAGER,
                'created_at' => '2026-11-20',
            ],
            true,
        ];

        yield 'no active review committee decision, expiry date in the future, submission rejected => 200 OK w/ Host Headers' => [
            '2026-11-01',
            ['active_from' => '2027-02-15'],
            // TODO: don't make submission create_at and action created_at the same datetime
            ['created_at' => '2026-11-20'],
            [
                // TODO: we should really create the previous Actions first
                'type' => ReviewSubmissionActionType::REJECTED,
                'actor_user_role' => UserRole::WIKI_MANAGER,
                'created_at' => '2026-11-20',
            ],
            true,
        ];

        yield 'no active review committee decision, expiry date in the future, submission cancelled => 200 OK w/ Host Headers' => [
            '2026-11-01',
            ['active_from' => '2027-02-15'],
            // TODO: don't make submission create_at and action created_at the same datetime
            ['created_at' => '2026-11-20'],
            [
                // TODO: we should really create the previous Actions first
                'type' => ReviewSubmissionActionType::CANCELLED,
                'actor_user_role' => UserRole::WIKI_MANAGER,
                'created_at' => '2026-11-20',
            ],
            true,
        ];

        yield 'no active review committee decision, expiry date in the past, submission submitted before expiry => 200 OK' => [
            '2026-11-01',
            ['active_from' => '2026-11-10'],
            ['created_at' => '2026-11-05'],
            [
                'type' => ReviewSubmissionActionType::SUBMITTED,
                'actor_user_role' => UserRole::WIKI_MANAGER,
                'created_at' => '2026-11-05',
            ],
            true,
        ];

        yield 'no active review committee decision, expiry date in the past, submission submitted after expiry => 403 Wiki suspended' => [
            '2026-11-01',
            ['active_from' => '2026-11-10'],
            ['created_at' => '2026-11-20'],
            [
                'type' => ReviewSubmissionActionType::SUBMITTED,
                'actor_user_role' => UserRole::WIKI_MANAGER,
                'created_at' => '2026-11-20',
            ],
            false,
        ];

        yield 'no active review committee decision, expiry date in the past, submission submitted before expiry and review started => 200 OK w/ Host Headers' => [
            '2026-11-01',
            ['active_from' => '2026-11-10'],
            // TODO: Make review not start on the same datetime it was created
            ['created_at' => '2026-11-05'],
            [
                // TODO: we should really create the previous Actions first
                'type' => ReviewSubmissionActionType::REVIEW_STARTED,
                'actor_user_role' => UserRole::WIKI_MANAGER,
                'created_at' => '2026-11-05',
            ],
            true,
        ];

        yield 'no active review committee decision, expiry date in the past, submission submitted after expiry and review started => 200 OK w/ Host Headers' => [
            '2026-11-01',
            ['active_from' => '2026-11-10'],
            // TODO: Make review not start on the same datetime it was created
            ['created_at' => '2026-11-20'],
            [
                // TODO: we should really create the previous Actions first
                'type' => ReviewSubmissionActionType::REVIEW_STARTED,
                'actor_user_role' => UserRole::WIKI_MANAGER,
                'created_at' => '2026-11-20',
            ],
            true,
        ];

        yield 'no active review committee decision, expiry date in the past, no submission => 403 Wiki suspended' => [
            '2026-11-01',
            ['active_from' => '2026-11-10'],
            null,
            null,
            false,
        ];

        yield 'no active review committee decision, expiry date in the past, submission submitted before expiry and cancelled => 403 Wiki suspended' => [
            '2026-11-01',
            ['active_from' => '2026-11-10'],
            // TODO: Make review not start on the same datetime it was created
            ['created_at' => '2026-11-05'],
            [
                // TODO: we should really create the previous Actions first
                'type' => ReviewSubmissionActionType::CANCELLED,
                'actor_user_role' => UserRole::WIKI_MANAGER,
                'created_at' => '2026-11-05',
            ],
            false,
        ];

        yield 'no active review committee decision, expiry date in the past, submission submitted after expiry and cancelled => 403 Wiki suspended' => [
            '2026-11-01',
            ['active_from' => '2026-11-10'],
            // TODO: Make review not start on the same datetime it was created
            ['created_at' => '2026-11-20'],
            [
                // TODO: we should really create the previous Actions first
                'type' => ReviewSubmissionActionType::CANCELLED,
                'actor_user_role' => UserRole::WIKI_MANAGER,
                'created_at' => '2026-11-20',
            ],
            false,
        ];

        yield 'no active review committee decision, expiry date in the past, submission submitted before expiry and approved => 403 Wiki suspended' => [
            '2026-11-01',
            ['active_from' => '2026-11-10'],
            // TODO: Make review not start on the same datetime it was created
            ['created_at' => '2026-11-05'],
            [
                // TODO: we should really create the previous Actions first
                'type' => ReviewSubmissionActionType::APPROVED,
                'actor_user_role' => UserRole::WIKI_MANAGER,
                'created_at' => '2026-11-05',
            ],
            false,
        ];

        yield 'no active review committee decision, expiry date in the past, submission submitted after expiry and approved => 403 Wiki suspended' => [
            '2026-11-01',
            ['active_from' => '2026-11-10'],
            // TODO: Make review not start on the same datetime it was created
            ['created_at' => '2026-11-20'],
            [
                // TODO: we should really create the previous Actions first
                'type' => ReviewSubmissionActionType::APPROVED,
                'actor_user_role' => UserRole::WIKI_MANAGER,
                'created_at' => '2026-11-20',
            ],
            false,
        ];

        yield 'no active review committee decision, expiry date in the past, submission submitted before expiry and rejected => 403 Wiki suspended' => [
            '2026-11-01',
            ['active_from' => '2026-11-10'],
            // TODO: Make review not start on the same datetime it was created
            ['created_at' => '2026-11-05'],
            [
                // TODO: we should really create the previous Actions first
                'type' => ReviewSubmissionActionType::REJECTED,
                'actor_user_role' => UserRole::WIKI_MANAGER,
                'created_at' => '2026-11-05',
            ],
            false,
        ];

        yield 'no active review committee decision, expiry date in the past, submission submitted after expiry and rejected => 403 Wiki suspended' => [
            '2026-11-01',
            ['active_from' => '2026-11-10'],
            // TODO: Make review not start on the same datetime it was created
            ['created_at' => '2026-11-20'],
            [
                // TODO: we should really create the previous Actions first
                'type' => ReviewSubmissionActionType::REJECTED,
                'actor_user_role' => UserRole::WIKI_MANAGER,
                'created_at' => '2026-11-20',
            ],
            false,
        ];
    }

    #[DataProvider('providePlatformNginxWikiInfoControllerCases')]
    public function testPlatformNginxWikiInfoController(
        string $wikiCreatedAt,
        ?array $wikiSuspensionState,
        ?array $reviewSubmissionState,
        ?array $reviewSubmissionActionState,
        bool $wikiExpectedToBeAccessible,
    ): void {
        $this->travelTo('2026-11-24');

        $wiki = Wiki::factory(['created_at' => $wikiCreatedAt])
            ->has(WikiDb::factory(['version' => 'mw1.43-wbs2']))
            ->create();

        if ($wikiSuspensionState !== null) {
            WikiSuspension::factory($wikiSuspensionState)->for($wiki)->create();
        }

        if ($reviewSubmissionState !== null) {
            ReviewSubmission::factory($reviewSubmissionState)
                ->for($wiki)
                ->when(
                    $reviewSubmissionActionState !== null,
                    fn ($factory) => $factory->hasActions($reviewSubmissionActionState)
                )
                ->create();
        }

        // FIXME: The PlatformNginxWikiInfoController creates 5-6 database queries
        // Uncomment to see the number of database queries are made in the preceding code
        // $this->expectsDatabaseQueryCount(0);

        $response = $this->get("{$this->route}?domain=$wiki->domain")
            ->assertStatus($wikiExpectedToBeAccessible ? 200 : 403)
            ->assertContent($wikiExpectedToBeAccessible ? 'OK' : 'Wiki suspended');

        if ($wikiExpectedToBeAccessible) {
            $response->assertHeader('x-mw-alpha-host');
            $response->assertHeader('x-mw-api-host');
            $response->assertHeader('x-mw-backend-host');
            $response->assertHeader('x-mw-web-host');
        }
    }

    // FUTURE: Possible helper method?
    // private function createReviewSubmission(
    //     Wiki $wiki,
    //     string $createdAt,
    //     ?ReviewSubmissionActionType $latestActionType,
    //     string $latestActionCreatedAt,
    // ): ReviewSubmission {
    //     $submission = ReviewSubmission::factory(['created_at' => $createdAt])
    //         ->for($wiki)
    //         ->create();

    //     if ($latestActionType === null) {
    //         return $submission;
    //     }

    //     ReviewSubmissionAction::factory()->for($submission)->create([
    //         'type' => ReviewSubmissionActionType::SUBMITTED,
    //         'actor_user_role' => UserRole::WIKI_MANAGER,
    //         'created_at' => $createdAt,
    //     ]);

    //     if ($latestActionType !== ReviewSubmissionActionType::SUBMITTED) {
    //         ReviewSubmissionAction::factory()->for($submission)->create([
    //             'type' => ReviewSubmissionActionType::REVIEW_STARTED,
    //             'actor_user_role' => UserRole::REVIEW_COMMITTEE_ADMIN,
    //             'created_at' => $latestActionType === ReviewSubmissionActionType::REVIEW_STARTED
    //                 ? $latestActionCreatedAt : $createdAt,
    //         ]);

    //         if ($latestActionType !== ReviewSubmissionActionType::REVIEW_STARTED) {
    //             ReviewSubmissionAction::factory()->for($submission)->create([
    //                 'type' => $latestActionType,
    //                 'actor_user_role' => UserRole::REVIEW_COMMITTEE_ADMIN,
    //                 'created_at' => $latestActionCreatedAt,
    //             ]);
    //         }
    //     }

    //     return $submission->refresh();
    // }
}
