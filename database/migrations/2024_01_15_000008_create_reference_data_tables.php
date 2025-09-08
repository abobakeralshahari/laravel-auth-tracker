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
        // Create countries table
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->unique(); // ISO country code
            $table->string('name', 100);
            $table->string('currency', 3)->nullable();
            $table->string('timezone', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['code']);
            $table->index(['is_active']);
        });
        
        // Create timezones table
        Schema::create('timezones', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('offset', 10); // UTC offset
            $table->string('country_code', 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['name']);
            $table->index(['country_code']);
        });
        
        // Create languages table
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 5)->unique(); // ISO language code
            $table->string('name', 100);
            $table->string('native_name', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['code']);
            $table->index(['is_active']);
        });
        
        // Create operating_systems table
        Schema::create('operating_systems', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('version', 20)->nullable();
            $table->string('family', 50); // Windows, macOS, Linux, iOS, Android
            $table->string('architecture', 20)->nullable(); // x86, x64, arm, etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['name']);
            $table->index(['family']);
        });
        
        // Create browsers table
        Schema::create('browsers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('version', 20)->nullable();
            $table->string('engine', 50)->nullable(); // WebKit, Gecko, Blink, etc.
            $table->boolean('is_mobile')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['name']);
            $table->index(['engine']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('browsers');
        Schema::dropIfExists('operating_systems');
        Schema::dropIfExists('languages');
        Schema::dropIfExists('timezones');
        Schema::dropIfExists('countries');
    }
};
