<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDevicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('deviceable');
            
            // Basic device information
            $table->string('udid', 100)->unique()->index();
            $table->string('os', 20)->nullable()->index();
            $table->string('os_version', 50)->nullable();
            $table->string('manufacturer', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('app_version', 20)->nullable()->index();
            $table->string('app_type', 20)->nullable()->index();
            $table->string('tenant', 10)->nullable()->index();
            $table->text('user_agent')->nullable();
            
            // Additional device attributes
            $table->string('screen_resolution', 20)->nullable();
            $table->string('timezone', 50)->nullable();
            $table->string('language', 10)->nullable();
            $table->integer('battery_level')->nullable();
            $table->string('network_type', 20)->nullable();
            $table->string('carrier', 100)->nullable();
            
            // Security fields
            $table->string('client_id', 100)->unique()->nullable()->index();
            $table->string('device_token', 255)->nullable()->index();
            $table->string('api_key', 255)->nullable()->index();
            
            // Status fields
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_trusted')->default(false)->index();
            $table->timestamp('trusted_at')->nullable();
            
            // Token management
            $table->timestamp('token_generated_at')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamp('token_refreshed_at')->nullable();
            $table->timestamp('api_key_generated_at')->nullable();
            $table->timestamp('api_key_expires_at')->nullable();
            
            // Activity tracking
            $table->timestamp('last_seen_at')->nullable()->index();
            
            // FCM token for push notifications
            $table->text('fcm_token')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['deviceable_type', 'deviceable_id']);
            $table->index(['is_active', 'last_seen_at']);
            $table->index(['os', 'manufacturer']);
            $table->index(['created_at', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('devices');
    }
}
