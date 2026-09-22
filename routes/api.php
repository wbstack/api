<?php

use App\Http\Controllers\ReviewSubmissionController;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\AuthorisedUsersForDeletedWikiMetricsMiddleware;
use App\Http\Middleware\LimitWikiAccess;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Routing\Middleware\SubstituteBindings;
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

    $router->apiResource('wiki', 'PublicWikiController')->only(['index', 'show']);
    $router->apiResource('reusePrototype', 'PublicWikiController')->only(['index']);
    $router->apiResource('wikiConversionData', 'ConversionMetricController')->only(['index']);

    Route::apiResource('/v1/wikis.review_submissions', ReviewSubmissionController::class)
        // TODO: the order middleware is listed here, is the order they will be executed in, is this order correct?
        // The `$middlewarePriority` property in app/Http/Kernel.php also defines priority of "non-global" middleware?
        ->middleware([
            Authenticate::class . ':api',
            EnsureEmailIsVerified::class,
            // This middleware is currently required to make Laravel's Route Model Bindings work
            // https://laravel.com/framework/docs/11.x/routing#route-model-binding
            // If we register routes as Laravel expects, we likely won't need to manually specify this
            // TODO: this returns the error response `{"message": "No query results for model [App\\Wiki] <wiki_id>"}`
            // if an invalid wiki_id (such as `-1`, `abc`, `999999`) is requested - do we want to improve this error response?
            SubstituteBindings::class,
            LimitWikiAccess::class,
        ])
        // Ensure that the ReviewSubmission belongs to the Wiki, for endpoints that have both as path params (show/update/destroy)
        ->scoped()
        ->only('index', 'store', 'show');
});
