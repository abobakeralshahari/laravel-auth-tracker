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
        // Create package_configurations table
        Schema::create('package_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value');
            $table->string('type', 20)->default('string'); // string, integer, boolean, json
            $table->text('description')->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();
            
            $table->index(['key']);
        });
        
        // Create feature_flags table
        Schema::create('feature_flags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->boolean('is_enabled')->default(false);
            $table->text('description')->nullable();
            $table->json('conditions')->nullable(); // Feature flag conditions
            $table->timestamp('enabled_at')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->timestamps();
            
            $table->index(['name']);
            $table->index(['is_enabled']);
        });
        
        // Create device_types table
        Schema::create('device_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('os', 50);
            $table->string('category', 50); // mobile, desktop, tablet, etc.
            $table->json('attributes')->nullable(); // Default attributes for this device type
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['os']);
            $table->index(['category']);
        });
        
        // Create notification_rules table
        Schema::create('notification_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('event_type', 50);
            $table->string('channel', 50);
            $table->json('conditions')->nullable(); // Rule conditions
            $table->json('template_data')->nullable(); // Template-specific data
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->timestamps();
            
            $table->index(['event_type']);
            $table->index(['channel']);
            $table->index(['is_active']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notification_rules');
        Schema::dropIfExists('device_types');
        Schema::dropIfExists('feature_flags');
        Schema::dropIfExists('package_configurations');
    }
};
