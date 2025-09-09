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
        // Create api_keys table
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->string('name', 100);
            $table->string('key', 100)->unique();
            $table->string('secret', 100);
            $table->json('permissions')->nullable(); // API permissions
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            
            $table->index(['device_id']);
            $table->index(['key']);
            $table->index(['is_active']);
        });
        
        // Create device_sessions table
        Schema::create('device_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->string('session_id', 100)->unique();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->timestamp('last_activity_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['device_id', 'is_active']);
            $table->index(['session_id']);
            $table->index(['last_activity_at']);
        });
        
        // Create device_trust_sessions table for trust management
        Schema::create('device_trust_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->string('session_id', 100);
            $table->timestamp('trusted_at');
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['device_id', 'is_active']);
            $table->index(['session_id']);
            $table->index(['expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('device_trust_sessions');
        Schema::dropIfExists('device_sessions');
        Schema::dropIfExists('api_keys');
    }
};
