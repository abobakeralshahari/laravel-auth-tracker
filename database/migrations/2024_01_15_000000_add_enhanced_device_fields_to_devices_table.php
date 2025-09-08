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
            // Enhanced device attributes
            $table->string('app_type', 50)->nullable()->after('udid');
            $table->string('tenant', 100)->nullable()->after('app_type');
            $table->string('screen_resolution', 20)->nullable()->after('tenant');
            $table->string('timezone', 50)->nullable()->after('screen_resolution');
            $table->string('language', 10)->nullable()->after('timezone');
            $table->integer('battery_level')->nullable()->after('language');
            $table->string('network_type', 20)->nullable()->after('battery_level');
            $table->string('carrier', 100)->nullable()->after('network_type');
            
            // Security fields
            $table->string('client_id', 100)->unique()->nullable()->after('carrier');
            $table->string('device_token', 500)->nullable()->after('client_id');
            $table->string('api_key', 500)->nullable()->after('device_token');
            
            // Status fields
            $table->boolean('is_active')->default(true)->after('api_key');
            $table->boolean('is_trusted')->default(false)->after('is_active');
            $table->timestamp('trusted_at')->nullable()->after('is_trusted');
            
            // Token management
            $table->timestamp('token_generated_at')->nullable()->after('trusted_at');
            $table->timestamp('token_expires_at')->nullable()->after('token_generated_at');
            $table->timestamp('token_refreshed_at')->nullable()->after('token_expires_at');
            
            // API key management
            $table->timestamp('api_key_generated_at')->nullable()->after('token_refreshed_at');
            $table->timestamp('api_key_expires_at')->nullable()->after('api_key_generated_at');
            
            // Activity tracking
            $table->timestamp('last_seen_at')->nullable()->after('api_key_expires_at');
            
            // FCM token for push notifications
            $table->text('fcm_token')->nullable()->after('last_seen_at');
            
            // Soft deletes
            $table->softDeletes()->after('fcm_token');
        });
        
        // Add indexes for performance
        Schema::table('devices', function (Blueprint $table) {
            $table->index(['client_id'], 'devices_client_id_index');
            $table->index(['device_token'], 'devices_device_token_index');
            $table->index(['api_key'], 'devices_api_key_index');
            $table->index(['is_active'], 'devices_is_active_index');
            $table->index(['is_trusted'], 'devices_is_trusted_index');
            $table->index(['last_seen_at'], 'devices_last_seen_at_index');
            $table->index(['user_id', 'is_active'], 'devices_user_active_index');
            $table->index(['user_id', 'is_trusted'], 'devices_user_trusted_index');
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
            // Drop indexes first
            $table->dropIndex('devices_client_id_index');
            $table->dropIndex('devices_device_token_index');
            $table->dropIndex('devices_api_key_index');
            $table->dropIndex('devices_is_active_index');
            $table->dropIndex('devices_is_trusted_index');
            $table->dropIndex('devices_last_seen_at_index');
            $table->dropIndex('devices_user_active_index');
            $table->dropIndex('devices_user_trusted_index');
            
            // Drop columns
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
                'last_seen_at',
                'fcm_token',
                'deleted_at'
            ]);
        });
    }
};
