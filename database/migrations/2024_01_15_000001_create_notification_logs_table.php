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
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // login, suspicious_login, device_registered, etc.
            $table->string('channel'); // email, push, database, webhook
            $table->morphs('notifiable'); // user, device, etc.
            $table->text('data')->nullable(); // notification data
            $table->boolean('sent')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['type', 'sent', 'created_at']);
            $table->index(['notifiable_type', 'notifiable_id']);
            $table->index(['sent', 'next_retry_at']);
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
    }
};
