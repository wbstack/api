<?php

use App\Http\Controllers\ReviewSubmissionController;
use App\Http\Middleware\AuthorisedUsersForDeletedWikiMetricsMiddleware;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Config;

/**
 * This route file is loaded in the RouteServiceProvider optionally when an env var is set.
 * You'll find that service in the Providers directory.
 *
 * @var Router $router
 */
$router->group(['middleware' => ['throttle:45,1']], function () use ($router): void {
    // TODO actually use logout route in VUE app..
    $router->post('user/register', [
        'middleware' => ['throttle.signup:' . Config::get('wbstack.signup_throttling_limit') . ',' . Config::get('wbstack.signup_throttling_range')],
        'uses' => 'Auth\RegisterController@register',
    ]);
    $router->post('user/verifyEmail', ['uses' => 'UserVerificationTokenController@verify']);
    $router->post('user/forgotPassword', ['uses' => 'Auth\ForgotPasswordController@sendResetLinkEmail']);
    $router->post('user/resetPassword', ['uses' => 'Auth\ResetPasswordController@reset']);
    $router->post('contact/sendMessage', ['uses' => 'ContactController@sendMessage']);
    $router->post('complaint/sendMessage', ['uses' => 'ComplaintController@sendMessage']);

    $router->post('auth/login', ['uses' => 'Auth\LoginController@postLogin'])->name('login');
    // Authed
    $router->group(['middleware' => ['auth:api']], function () use ($router): void {
        $router->get('auth/login', ['uses' => 'Auth\LoginController@getLogin']);
        $router->delete('auth/login', ['uses' => 'Auth\LoginController@deleteLogin']);
        $router->get('v1/policies/missing', ['uses' => 'PoliciesController@getMissingPolicies']);

        // policy acceptances
        $router->put('v1/policy_acceptances', ['uses' => 'PolicyAcceptanceController@store']);

        // user
        $router->group(['prefix' => 'user'], function () use ($router): void {
            $router->post('sendVerifyEmail', ['uses' => 'UserVerificationTokenController@createAndSendForUser']);
        });

        // wiki
        // TODO wiki id should probably be in the path of most of these routes...
        $router->group(['prefix' => 'wiki'], function () use ($router): void {
            // TODO maybe the UI just shouldn't make this request if users are not verified...
            $router->post('mine', ['uses' => 'WikisController@getWikisOwnedByCurrentUser']);
        });
        $router->group(['prefix' => 'wiki', 'middleware' => ['verified']], function () use ($router): void {
            $router->post('create', ['uses' => 'WikiController@create']);
            $router->group(['middleware' => 'limit_wiki_access'], function () use ($router): void {
                $router->post('delete', ['uses' => 'WikiController@delete']);
                $router->post('details', ['uses' => 'WikiController@getWikiDetailsForIdForOwner']);
                $router->get('details', ['uses' => 'WikiController@getWikiDetailsForIdForOwner']);
                $router->post('logo/update', ['uses' => 'WikiLogoController@update']);
                $router->post('setting/{setting}/update', ['uses' => 'WikiSettingController@update']);
                $router->get('entityImport', ['uses' => 'WikiEntityImportController@get']);
                $router->post('entityImport', ['uses' => 'WikiEntityImportController@create']);
                $router->post('profile', ['uses' => 'WikiProfileController@create']);
            });
        });
        $router->apiResource('deletedWikiMetrics', 'DeletedWikiMetricsController')->only(['index'])
            ->middleware(AuthorisedUsersForDeletedWikiMetricsMiddleware::class);
    });

    $router->get('v1/policies/current', ['uses' => 'PoliciesController@getCurrentPolicies']);
    $router->get('v1/policies/{policy_type}/current', ['uses' => 'PolicyController@getCurrentPolicyByType']);
    $router->get('v1/policies/{policy_type}/upcoming', ['uses' => 'PolicyController@getUpcomingPolicyByType']);
    $router->get('v1/policies/{policy_type}/by_active_from/{active_from}', ['uses' => 'PolicyController@getPolicyByTypeAndActiveFrom']);
    $router->get('v1/policies/{policy_type}', ['uses' => 'PoliciesController@getPoliciesByType']);

    // TODO: Should this actually go in the Wikis block?
    //  - A: No, while this looks similar to the wiki above, we want a fresh start following a RESTful pattern with versioning
    //
    // TODO: this currently has no middleware to restrict putting only submissions for Wikis you own here
    //
    // The Laravel docs on routing[1] start off by showing an example of a manually defined `UserController` with minimal Laravel magic.
    // It then goes on to talk about redirects, views, listing routes, routing customizations, optional and required route parameters,
    // named routes, route groups, middleware, controllers, subdomains, prefixes, etc. before mentioning route model binding as an option.
    // I don't think that using model binding for this POST endpoint will benefit us as we don't need to get the Wiki from the database.
    // [1]: https://laravel.com/framework/docs/11.x/routing
    //
    // TODO: All of the below route definitions work - we just need to choose one.
    //   - $router->post('/v1/wikis/{wiki_id}/review_submissions', ['uses' => 'ReviewSubmissionController@store']);
    //   - $router->post('/v1/wikis/{wiki_id}/review_submissions', [ReviewSubmissionController::class, 'store']);
    //   - Route::post('/v1/wikis/{wiki_id}/review_submissions', [ReviewSubmissionController::class, 'store']);
    //   - Route::apiResource('/v1/wikis.review_submissions', ReviewSubmissionController::class)->parameter('wikis', 'wiki_id')->only('store');
    // If you want these too look more like the Laravel docs / "the approved way" then I suggest we use one of the last two.

    Route::apiResource('/v1/wikis.review_submissions', ReviewSubmissionController::class)
        ->parameter('wikis', 'wiki_id')
        ->parameter('review_submissions', 'review_submission_id')
        // this is only regex validation and we probably don't want to clutter up the route files with regex parameter validation?
        ->whereNumber('wiki_id');

    // TODO: Remove - just an example showing what a '/v1/wikis' endpoint could look like.
    // Use php artisan route:list --path='wikis' to see all the endpoints created by these two new apiResources
    Route::apiResource('/v1/wikis', ReviewSubmissionController::class)
        ->parameter('wikis', 'wiki_id');

    $router->apiResource('wiki', 'PublicWikiController')->only(['index', 'show']);
    $router->apiResource('reusePrototype', 'PublicWikiController')->only(['index']);
    $router->apiResource('wikiConversionData', 'ConversionMetricController')->only(['index']);
});
