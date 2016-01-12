<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOAuthCollections extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // @TODO: Are access tokens always within a session??? And if so, do we ever fetch them directly?
        Schema::create('access_tokens', function ($collection) {
            $collection->index('access_token');
        });

        Schema::create('oauth_clients', function ($collection) {
            $collection->index('secret');
        });

        Schema::create('oauth_sessions', function ($collection) {
            $collection->index('session_id');
        });

        Schema::create('oauth_refresh_tokens', function ($collection) {
            $collection->index('refresh_token');
        });

        Schema::create('oauth_scopes', function ($collection) {
            // id
        });


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('access_tokens', function ($collection) {
            $collection->dropIndex('access_token');
        });

        Schema::table('oauth_clients', function ($collection) {
            $collection->dropIndex('access_token');
        });
    }
}
