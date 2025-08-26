<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Passport now has support for multiple guard user providers.
 * Because of this change, you must add a provider column to the oauth_clients database table:
 * https://github.com/laravel/passport/blob/66b9088993f1be9a4a95129b61dca951e04223ff/UPGRADE.md#support-for-multiple-guards.
 *
 * As of writing this, this feature is not yet being turned on!
 */
class ModifyOauthClientsProvider extends Migration {
    public function up() {
        if (!Schema::hasColumn('oauth_clients', 'provider')) {
            Schema::table('oauth_clients', function (Blueprint $table) {
                $table->string('provider')->after('secret')->nullable();
            });
        }
    }

    public function down() {
        Schema::table('oauth_clients', function ($table) {
            $table->dropColumn('provider');
        });
    }
}
