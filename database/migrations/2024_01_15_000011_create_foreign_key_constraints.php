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
        // Add foreign key constraints for better data integrity
        
        // Device tokens foreign keys
        Schema::table('device_tokens', function (Blueprint $table) {
            $table->foreign('device_id')->references('id')->on('devices')->onDelete('cascade');
        });
        
        // Device notifications foreign keys
        Schema::table('device_notifications', function (Blueprint $table) {
            $table->foreign('device_id')->references('id')->on('devices')->onDelete('cascade');
        });
        
        // Device activities foreign keys
        Schema::table('device_activities', function (Blueprint $table) {
            $table->foreign('device_id')->references('id')->on('devices')->onDelete('cascade');
        });
        
        // Device trust sessions foreign keys
        Schema::table('device_trust_sessions', function (Blueprint $table) {
            $table->foreign('device_id')->references('id')->on('devices')->onDelete('cascade');
        });
        
        // API keys foreign keys
        Schema::table('api_keys', function (Blueprint $table) {
            $table->foreign('device_id')->references('id')->on('devices')->onDelete('cascade');
        });
        
        // Device sessions foreign keys
        Schema::table('device_sessions', function (Blueprint $table) {
            $table->foreign('device_id')->references('id')->on('devices')->onDelete('cascade');
        });
        
        // Device fingerprints foreign keys
        Schema::table('device_fingerprints', function (Blueprint $table) {
            $table->foreign('device_id')->references('id')->on('devices')->onDelete('cascade');
        });
        
        // User sessions foreign keys
        Schema::table('user_sessions', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('device_id')->references('id')->on('devices')->onDelete('set null');
        });
        
        // Device statistics foreign keys
        Schema::table('device_statistics', function (Blueprint $table) {
            $table->foreign('device_id')->references('id')->on('devices')->onDelete('cascade');
        });
        
        // User statistics foreign keys
        Schema::table('user_statistics', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
        
        // Timezones foreign keys
        Schema::table('timezones', function (Blueprint $table) {
            $table->foreign('country_code')->references('code')->on('countries')->onDelete('set null');
        });
        
        // Add foreign key constraints for logins table if it exists
        if (Schema::hasTable('logins')) {
            Schema::table('logins', function (Blueprint $table) {
                if (Schema::hasColumn('logins', 'device_id')) {
                    $table->foreign('device_id')->references('id')->on('devices')->onDelete('set null');
                }
            });
        }
        
        // Add foreign key constraints for devices table if it exists
        if (Schema::hasTable('devices')) {
            Schema::table('devices', function (Blueprint $table) {
                if (Schema::hasColumn('devices', 'user_id')) {
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop foreign key constraints
        
        // Device tokens foreign keys
        Schema::table('device_tokens', function (Blueprint $table) {
            $table->dropForeign(['device_id']);
        });
        
        // Device notifications foreign keys
        Schema::table('device_notifications', function (Blueprint $table) {
            $table->dropForeign(['device_id']);
        });
        
        // Device activities foreign keys
        Schema::table('device_activities', function (Blueprint $table) {
            $table->dropForeign(['device_id']);
        });
        
        // Device trust sessions foreign keys
        Schema::table('device_trust_sessions', function (Blueprint $table) {
            $table->dropForeign(['device_id']);
        });
        
        // API keys foreign keys
        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropForeign(['device_id']);
        });
        
        // Device sessions foreign keys
        Schema::table('device_sessions', function (Blueprint $table) {
            $table->dropForeign(['device_id']);
        });
        
        // Device fingerprints foreign keys
        Schema::table('device_fingerprints', function (Blueprint $table) {
            $table->dropForeign(['device_id']);
        });
        
        // User sessions foreign keys
        Schema::table('user_sessions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['device_id']);
        });
        
        // Device statistics foreign keys
        Schema::table('device_statistics', function (Blueprint $table) {
            $table->dropForeign(['device_id']);
        });
        
        // User statistics foreign keys
        Schema::table('user_statistics', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        
        // Timezones foreign keys
        Schema::table('timezones', function (Blueprint $table) {
            $table->dropForeign(['country_code']);
        });
        
        // Drop foreign key constraints for logins table if it exists
        if (Schema::hasTable('logins')) {
            Schema::table('logins', function (Blueprint $table) {
                if (Schema::hasColumn('logins', 'device_id')) {
                    $table->dropForeign(['device_id']);
                }
            });
        }
        
        // Drop foreign key constraints for devices table if it exists
        if (Schema::hasTable('devices')) {
            Schema::table('devices', function (Blueprint $table) {
                if (Schema::hasColumn('devices', 'user_id')) {
                    $table->dropForeign(['user_id']);
                }
            });
        }
    }
};
