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
        // Create device_statistics table
        Schema::create('device_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->integer('login_count')->default(0);
            $table->integer('logout_count')->default(0);
            $table->integer('api_calls_count')->default(0);
            $table->integer('notification_sent_count')->default(0);
            $table->integer('error_count')->default(0);
            $table->timestamp('first_activity_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
            
            $table->unique(['device_id', 'date']);
            $table->index(['date']);
        });
        
        // Create user_statistics table
        Schema::create('user_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->integer('total_devices')->default(0);
            $table->integer('active_devices')->default(0);
            $table->integer('trusted_devices')->default(0);
            $table->integer('login_count')->default(0);
            $table->integer('suspicious_activities')->default(0);
            $table->timestamp('first_login_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'date']);
            $table->index(['date']);
        });
        
        // Create system_statistics table
        Schema::create('system_statistics', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->integer('total_users')->default(0);
            $table->integer('total_devices')->default(0);
            $table->integer('active_devices')->default(0);
            $table->integer('total_logins')->default(0);
            $table->integer('suspicious_activities')->default(0);
            $table->integer('notifications_sent')->default(0);
            $table->integer('api_calls')->default(0);
            $table->timestamps();
            
            $table->unique(['date']);
            $table->index(['date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('system_statistics');
        Schema::dropIfExists('user_statistics');
        Schema::dropIfExists('device_statistics');
    }
};
