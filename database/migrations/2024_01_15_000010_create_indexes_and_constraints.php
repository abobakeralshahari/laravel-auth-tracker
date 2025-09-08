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
        // Add additional indexes for performance optimization
        Schema::table('devices', function (Blueprint $table) {
            // Composite indexes for common queries
            $table->index(['user_id', 'is_active', 'last_seen_at'], 'devices_user_active_last_seen');
            $table->index(['user_id', 'is_trusted', 'created_at'], 'devices_user_trusted_created');
            $table->index(['os', 'is_active'], 'devices_os_active');
            $table->index(['manufacturer', 'is_active'], 'devices_manufacturer_active');
            $table->index(['created_at', 'is_active'], 'devices_created_active');
        });
        
        Schema::table('logins', function (Blueprint $table) {
            // Add indexes for login queries
            $table->index(['user_id', 'created_at'], 'logins_user_created');
            $table->index(['device_id', 'created_at'], 'logins_device_created');
            $table->index(['ip_address', 'created_at'], 'logins_ip_created');
            $table->index(['created_at', 'logout_at'], 'logins_created_logout');
        });
        
        Schema::table('device_tokens', function (Blueprint $table) {
            // Add indexes for token queries
            $table->index(['device_id', 'token_type', 'is_active'], 'device_tokens_device_type_active');
            $table->index(['token', 'is_active'], 'device_tokens_token_active');
            $table->index(['expires_at', 'is_active'], 'device_tokens_expires_active');
        });
        
        Schema::table('device_notifications', function (Blueprint $table) {
            // Add indexes for notification queries
            $table->index(['device_id', 'type', 'sent'], 'device_notifications_device_type_sent');
            $table->index(['type', 'sent', 'created_at'], 'device_notifications_type_sent_created');
            $table->index(['sent', 'created_at'], 'device_notifications_sent_created');
        });
        
        Schema::table('device_activities', function (Blueprint $table) {
            // Add indexes for activity queries
            $table->index(['device_id', 'activity_type', 'occurred_at'], 'device_activities_device_type_occurred');
            $table->index(['activity_type', 'occurred_at'], 'device_activities_type_occurred');
            $table->index(['ip_address', 'occurred_at'], 'device_activities_ip_occurred');
        });
        
        Schema::table('security_events', function (Blueprint $table) {
            // Add indexes for security queries
            $table->index(['event_type', 'resolved', 'created_at'], 'security_events_type_resolved_created');
            $table->index(['risk_score', 'resolved'], 'security_events_risk_resolved');
            $table->index(['ip_address', 'created_at'], 'security_events_ip_created');
        });
        
        Schema::table('rate_limits', function (Blueprint $table) {
            // Add indexes for rate limit queries
            $table->index(['key', 'type', 'reset_at'], 'rate_limits_key_type_reset');
            $table->index(['type', 'reset_at'], 'rate_limits_type_reset');
        });
        
        Schema::table('api_keys', function (Blueprint $table) {
            // Add indexes for API key queries
            $table->index(['device_id', 'is_active'], 'api_keys_device_active');
            $table->index(['key', 'is_active'], 'api_keys_key_active');
            $table->index(['expires_at', 'is_active'], 'api_keys_expires_active');
        });
        
        Schema::table('device_sessions', function (Blueprint $table) {
            // Add indexes for session queries
            $table->index(['device_id', 'is_active', 'last_activity_at'], 'device_sessions_device_active_last_activity');
            $table->index(['session_id', 'is_active'], 'device_sessions_session_active');
            $table->index(['last_activity_at', 'is_active'], 'device_sessions_last_activity_active');
        });
        
        Schema::table('login_attempts', function (Blueprint $table) {
            // Add indexes for login attempt queries
            $table->index(['email', 'attempted_at'], 'login_attempts_email_attempted');
            $table->index(['ip_address', 'attempted_at'], 'login_attempts_ip_attempted');
            $table->index(['successful', 'attempted_at'], 'login_attempts_successful_attempted');
        });
        
        Schema::table('device_fingerprints', function (Blueprint $table) {
            // Add indexes for fingerprint queries
            $table->index(['device_id', 'is_verified'], 'device_fingerprints_device_verified');
            $table->index(['fingerprint', 'is_verified'], 'device_fingerprints_fingerprint_verified');
            $table->index(['confidence_score', 'is_verified'], 'device_fingerprints_confidence_verified');
        });
        
        Schema::table('user_sessions', function (Blueprint $table) {
            // Add indexes for user session queries
            $table->index(['user_id', 'is_active', 'last_activity_at'], 'user_sessions_user_active_last_activity');
            $table->index(['device_id', 'is_active'], 'user_sessions_device_active');
            $table->index(['session_id', 'is_active'], 'user_sessions_session_active');
        });
        
        Schema::table('audit_logs', function (Blueprint $table) {
            // Add indexes for audit log queries
            $table->index(['event', 'created_at'], 'audit_logs_event_created');
            $table->index(['auditable_type', 'auditable_id', 'created_at'], 'audit_logs_auditable_created');
            $table->index(['ip_address', 'created_at'], 'audit_logs_ip_created');
        });
        
        Schema::table('device_statistics', function (Blueprint $table) {
            // Add indexes for device statistics queries
            $table->index(['device_id', 'date'], 'device_statistics_device_date');
            $table->index(['date', 'login_count'], 'device_statistics_date_login_count');
        });
        
        Schema::table('user_statistics', function (Blueprint $table) {
            // Add indexes for user statistics queries
            $table->index(['user_id', 'date'], 'user_statistics_user_date');
            $table->index(['date', 'total_devices'], 'user_statistics_date_total_devices');
        });
        
        Schema::table('system_statistics', function (Blueprint $table) {
            // Add indexes for system statistics queries
            $table->index(['date', 'total_users'], 'system_statistics_date_total_users');
            $table->index(['date', 'total_devices'], 'system_statistics_date_total_devices');
        });
        
        Schema::table('performance_metrics', function (Blueprint $table) {
            // Add indexes for performance metrics queries
            $table->index(['metric_name', 'recorded_at'], 'performance_metrics_name_recorded');
            $table->index(['metric_type', 'recorded_at'], 'performance_metrics_type_recorded');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop all the indexes we created
        Schema::table('devices', function (Blueprint $table) {
            $table->dropIndex('devices_user_active_last_seen');
            $table->dropIndex('devices_user_trusted_created');
            $table->dropIndex('devices_os_active');
            $table->dropIndex('devices_manufacturer_active');
            $table->dropIndex('devices_created_active');
        });
        
        Schema::table('logins', function (Blueprint $table) {
            $table->dropIndex('logins_user_created');
            $table->dropIndex('logins_device_created');
            $table->dropIndex('logins_ip_created');
            $table->dropIndex('logins_created_logout');
        });
        
        Schema::table('device_tokens', function (Blueprint $table) {
            $table->dropIndex('device_tokens_device_type_active');
            $table->dropIndex('device_tokens_token_active');
            $table->dropIndex('device_tokens_expires_active');
        });
        
        Schema::table('device_notifications', function (Blueprint $table) {
            $table->dropIndex('device_notifications_device_type_sent');
            $table->dropIndex('device_notifications_type_sent_created');
            $table->dropIndex('device_notifications_sent_created');
        });
        
        Schema::table('device_activities', function (Blueprint $table) {
            $table->dropIndex('device_activities_device_type_occurred');
            $table->dropIndex('device_activities_type_occurred');
            $table->dropIndex('device_activities_ip_occurred');
        });
        
        Schema::table('security_events', function (Blueprint $table) {
            $table->dropIndex('security_events_type_resolved_created');
            $table->dropIndex('security_events_risk_resolved');
            $table->dropIndex('security_events_ip_created');
        });
        
        Schema::table('rate_limits', function (Blueprint $table) {
            $table->dropIndex('rate_limits_key_type_reset');
            $table->dropIndex('rate_limits_type_reset');
        });
        
        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropIndex('api_keys_device_active');
            $table->dropIndex('api_keys_key_active');
            $table->dropIndex('api_keys_expires_active');
        });
        
        Schema::table('device_sessions', function (Blueprint $table) {
            $table->dropIndex('device_sessions_device_active_last_activity');
            $table->dropIndex('device_sessions_session_active');
            $table->dropIndex('device_sessions_last_activity_active');
        });
        
        Schema::table('login_attempts', function (Blueprint $table) {
            $table->dropIndex('login_attempts_email_attempted');
            $table->dropIndex('login_attempts_ip_attempted');
            $table->dropIndex('login_attempts_successful_attempted');
        });
        
        Schema::table('device_fingerprints', function (Blueprint $table) {
            $table->dropIndex('device_fingerprints_device_verified');
            $table->dropIndex('device_fingerprints_fingerprint_verified');
            $table->dropIndex('device_fingerprints_confidence_verified');
        });
        
        Schema::table('user_sessions', function (Blueprint $table) {
            $table->dropIndex('user_sessions_user_active_last_activity');
            $table->dropIndex('user_sessions_device_active');
            $table->dropIndex('user_sessions_session_active');
        });
        
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_event_created');
            $table->dropIndex('audit_logs_auditable_created');
            $table->dropIndex('audit_logs_ip_created');
        });
        
        Schema::table('device_statistics', function (Blueprint $table) {
            $table->dropIndex('device_statistics_device_date');
            $table->dropIndex('device_statistics_date_login_count');
        });
        
        Schema::table('user_statistics', function (Blueprint $table) {
            $table->dropIndex('user_statistics_user_date');
            $table->dropIndex('user_statistics_date_total_devices');
        });
        
        Schema::table('system_statistics', function (Blueprint $table) {
            $table->dropIndex('system_statistics_date_total_users');
            $table->dropIndex('system_statistics_date_total_devices');
        });
        
        Schema::table('performance_metrics', function (Blueprint $table) {
            $table->dropIndex('performance_metrics_name_recorded');
            $table->dropIndex('performance_metrics_type_recorded');
        });
    }
};
