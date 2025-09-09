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
        // Create login_attempts table
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('email', 255);
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->boolean('successful')->default(false);
            $table->string('failure_reason', 100)->nullable();
            $table->timestamp('attempted_at');
            $table->timestamps();
            
            $table->index(['email']);
            $table->index(['ip_address']);
            $table->index(['successful']);
            $table->index(['attempted_at']);
        });
        
        // Create device_fingerprints table
        Schema::create('device_fingerprints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->string('fingerprint', 500)->unique();
            $table->json('attributes'); // Device attributes used for fingerprinting
            $table->integer('confidence_score')->default(0); // 0-100 confidence
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            
            $table->index(['device_id']);
            $table->index(['fingerprint']);
            $table->index(['confidence_score']);
        });
        
        // Create user_sessions table
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('device_id')->nullable()->constrained()->onDelete('set null');
            $table->string('session_id', 100)->unique();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->timestamp('last_activity_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['user_id', 'is_active']);
            $table->index(['device_id']);
            $table->index(['session_id']);
            $table->index(['last_activity_at']);
        });
        
        // Create audit_logs table
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event', 100);
            $table->morphs('auditable'); // Polymorphic relation
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('url', 500)->nullable();
            $table->timestamps();
            
            $table->index(['event']);
            $table->index(['auditable_type', 'auditable_id']);
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
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('user_sessions');
        Schema::dropIfExists('device_fingerprints');
        Schema::dropIfExists('login_attempts');
    }
};
