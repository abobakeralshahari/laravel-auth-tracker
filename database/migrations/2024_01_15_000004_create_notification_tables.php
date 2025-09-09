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
        // Create notification_channels table
        Schema::create('notification_channels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('driver', 50); // email, push, database, webhook
            $table->json('config')->nullable(); // Channel-specific configuration
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        
        // Create notification_templates table
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50); // new_device, suspicious_login, etc.
            $table->string('channel', 50); // email, push, database, webhook
            $table->string('subject')->nullable();
            $table->text('body');
            $table->json('variables')->nullable(); // Available template variables
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['type', 'channel']);
        });
        
        // Create notification_logs table
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50);
            $table->string('channel', 50);
            $table->morphs('notifiable'); // Polymorphic relation
            $table->text('message');
            $table->json('data')->nullable();
            $table->boolean('sent')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamps();
            
            $table->index(['type', 'channel']);
            $table->index(['sent']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('notification_channels');
    }
};
