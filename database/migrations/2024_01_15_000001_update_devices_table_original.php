<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('devices', function (Blueprint $table) {
            // Update existing columns to be longer
            $table->string('udid', 500)->change(); // Make UDID longer and unique
            $table->string('os', 50)->change(); // Make OS field longer
            $table->string('model', 100)->change(); // Make model field longer
            $table->string('manufacturer', 100)->change(); // Make manufacturer field longer
            $table->string('app_version', 20)->change(); // Make app_version field longer
            
            // Add unique constraint to UDID
            $table->unique('udid', 'devices_udid_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('devices', function (Blueprint $table) {
            // Drop unique constraint
            $table->dropUnique('devices_udid_unique');
            
            // Revert column changes
            $table->string('udid', 255)->change();
            $table->string('os', 50)->change();
            $table->string('model', 50)->change();
            $table->string('manufacturer', 50)->change();
            $table->string('app_version', 10)->change();
        });
    }
};
