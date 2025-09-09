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
            // Enhanced device fields for better tracking
            $table->string('app_type', 50)->nullable()->after('udid');
            $table->string('tenant')->nullable()->after('app_type');
            $table->string('screen_resolution')->nullable()->after('tenant');
            $table->string('timezone')->nullable()->after('screen_resolution');
            $table->string('language', 10)->nullable()->after('timezone');
            $table->integer('battery_level')->nullable()->after('language');
            $table->string('network_type', 20)->nullable()->after('battery_level');
            $table->string('carrier')->nullable()->after('network_type');
            
            // Security fields
            $table->string('client_id')->unique()->nullable()->after('carrier');
            $table->string('device_token')->nullable()->index()->after('client_id');
            $table->string('api_key')->nullable()->index()->after('device_token');
            
            // Status fields
            $table->boolean('is_active')->default(true)->index()->after('api_key');
            $table->boolean('is_trusted')->default(false)->index()->after('is_active');
            $table->timestamp('trusted_at')->nullable()->after('is_trusted');
            
            // Token management
            $table->timestamp('token_generated_at')->nullable()->after('trusted_at');
            $table->timestamp('token_expires_at')->nullable()->after('token_generated_at');
            $table->timestamp('token_refreshed_at')->nullable()->after('token_expires_at');
            $table->timestamp('api_key_generated_at')->nullable()->after('token_refreshed_at');
            $table->timestamp('api_key_expires_at')->nullable()->after('api_key_generated_at');
            
            // Activity tracking
            $table->timestamp('last_seen_at')->nullable()->index()->after('api_key_expires_at');
            
            // Update FCM token to be longer
            $table->text('fcm_token')->nullable()->change();
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
            // Drop the new columns
            $table->dropColumn([
                'app_type',
                'tenant',
                'screen_resolution',
                'timezone',
                'language',
                'battery_level',
                'network_type',
                'carrier',
                'client_id',
                'device_token',
                'api_key',
                'is_active',
                'is_trusted',
                'trusted_at',
                'token_generated_at',
                'token_expires_at',
                'token_refreshed_at',
                'api_key_generated_at',
                'api_key_expires_at',
                'last_seen_at'
            ]);
            
            // Revert FCM token to original type
            $table->string('fcm_token')->nullable()->change();
        });
    }
};