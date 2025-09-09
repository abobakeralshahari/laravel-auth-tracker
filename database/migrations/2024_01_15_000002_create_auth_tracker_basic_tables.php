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
        // Create device_tokens table for token management
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->string('token_type', 20)->default('device'); // device, api, refresh
            $table->string('token', 500);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['device_id', 'token_type']);
            $table->index(['token']);
            $table->index(['expires_at']);
        });
        
        // Create device_notifications table for notification history
        Schema::create('device_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->string('type', 50); // new_device, suspicious_login, etc.
            $table->string('channel', 20); // email, push, database, webhook
            $table->text('message');
            $table->json('data')->nullable(); // Additional data
            $table->boolean('sent')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['device_id', 'type']);
            $table->index(['sent']);
            $table->index(['created_at']);
        });
        
        // Create device_activities table for activity tracking
        Schema::create('device_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->string('activity_type', 50); // login, logout, token_refresh, etc.
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable(); // Additional activity data
            $table->timestamp('occurred_at');
            $table->timestamps();
            
            $table->index(['device_id', 'activity_type']);
            $table->index(['occurred_at']);
            $table->index(['ip_address']);
        });
        
        // Create security_events table
        Schema::create('security_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 50); // suspicious_login, token_abuse, etc.
            $table->morphs('trackable'); // Polymorphic relation (device, user, etc.)
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable(); // Additional event data
            $table->integer('risk_score')->default(0); // 0-100 risk score
            $table->boolean('resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            
            $table->index(['event_type']);
            $table->index(['risk_score']);
            $table->index(['resolved']);
            $table->index(['created_at']);
        });
        
        // Create rate_limits table
        Schema::create('rate_limits', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100); // Rate limit key (IP, user, etc.)
            $table->string('type', 50); // api, login, token_refresh, etc.
            $table->integer('attempts')->default(0);
            $table->timestamp('reset_at');
            $table->timestamps();
            
            $table->unique(['key', 'type']);
            $table->index(['reset_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rate_limits');
        Schema::dropIfExists('security_events');
        Schema::dropIfExists('device_activities');
        Schema::dropIfExists('device_notifications');
        Schema::dropIfExists('device_tokens');
    }
};
