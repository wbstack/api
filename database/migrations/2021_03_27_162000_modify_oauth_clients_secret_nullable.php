<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * For passport public clients, not that we are using this feature right now...
 * https://github.com/laravel/passport/blob/66b9088993f1be9a4a95129b61dca951e04223ff/UPGRADE.md#public-clients
 */
class ModifyOauthClientsSecretNullable extends Migration
{
    public function up()
    {
        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->string('secret', 100)->nullable()->change();
        });
    }
    public function down()
    {
        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->string('secret', 100)->nullable(false)->change();
        });
    }
}
