<?php

use Illuminate\Database\Migrations\Migration;

class AddDrupalIDIndex extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mongodb')->table('users', function ($collection) {
            $collection->index('drupal_id', ['sparse' => true]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $collection->dropIndex('drupal_id');
    }
}
