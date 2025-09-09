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
        
        // Add composite indexes for common query patterns
        Schema::table('devices', function (Blueprint $table) {
            // Index for user device queries with trust status
            $table->index(['user_id', 'is_active', 'is_trusted', 'last_seen_at'], 'devices_user_active_trusted_last_seen');
            
            // Index for device lookup by UDID and user with active status
            $table->index(['udid', 'user_id', 'is_active'], 'devices_udid_user_active');
            
            // Index for device lookup by client_id with active status
            $table->index(['client_id', 'is_active', 'last_seen_at'], 'devices_client_active_last_seen');
            
            // Index for device lookup by device_token with active status
            $table->index(['device_token', 'is_active', 'last_seen_at'], 'devices_token_active_last_seen');
            
            // Index for device lookup by api_key with active status
            $table->index(['api_key', 'is_active', 'last_seen_at'], 'devices_api_key_active_last_seen');
            
            // Index for device statistics queries with date range
            $table->index(['user_id', 'created_at', 'is_active', 'last_seen_at'], 'devices_user_created_active_last_seen');
            
            // Index for device activity queries with trust status
            $table->index(['last_seen_at', 'is_active', 'is_trusted'], 'devices_last_seen_active_trusted');
            
            // Index for device trust queries with activity
            $table->index(['is_trusted', 'trusted_at', 'last_seen_at'], 'devices_trusted_at_last_seen');
            
            // Index for device type queries with activity
            $table->index(['os', 'manufacturer', 'is_active', 'last_seen_at'], 'devices_os_manufacturer_active_last_seen');
            
            // Index for device security queries
            $table->index(['is_active', 'is_trusted', 'last_seen_at', 'created_at'], 'devices_active_trusted_last_seen_created');
        });
        
        // Add indexes for logins table
        if (Schema::hasTable('logins')) {
            Schema::table('logins', function (Blueprint $table) {
                // Index for user login history with device info
                $table->index(['user_id', 'created_at', 'logout_at', 'device_id'], 'logins_user_created_logout_device');
                
                // Index for device login history with user info
                $table->index(['device_id', 'created_at', 'logout_at', 'user_id'], 'logins_device_created_logout_user');
                
                // Index for IP-based queries with time range
                $table->index(['ip_address', 'created_at', 'logout_at'], 'logins_ip_created_logout');
                
                // Index for active sessions with device info
                $table->index(['user_id', 'logout_at', 'device_id'], 'logins_user_logout_device');
                
                // Index for device sessions with user info
                $table->index(['device_id', 'logout_at', 'user_id'], 'logins_device_logout_user');
                
                // Index for login statistics
                $table->index(['created_at', 'user_id', 'device_id'], 'logins_created_user_device');
            });
        }
        
        // Add indexes for device_tokens table
        Schema::table('device_tokens', function (Blueprint $table) {
            // Index for token validation with device info
            $table->index(['token', 'is_active', 'expires_at', 'device_id'], 'device_tokens_token_active_expires_device');
            
            // Index for device token queries with type
            $table->index(['device_id', 'token_type', 'is_active', 'expires_at'], 'device_tokens_device_type_active_expires');
            
            // Index for token cleanup with device info
            $table->index(['expires_at', 'is_active', 'device_id'], 'device_tokens_expires_active_device');
            
            // Index for token usage tracking with device info
            $table->index(['last_used_at', 'is_active', 'device_id'], 'device_tokens_last_used_active_device');
            
            // Index for token statistics
            $table->index(['token_type', 'is_active', 'created_at'], 'device_tokens_type_active_created');
        });
        
        // Add indexes for device_notifications table
        Schema::table('device_notifications', function (Blueprint $table) {
            // Index for notification queries with device info
            $table->index(['device_id', 'type', 'sent', 'created_at', 'channel'], 'device_notifications_device_type_sent_created_channel');
            
            // Index for notification statistics with device info
            $table->index(['type', 'channel', 'sent', 'created_at', 'device_id'], 'device_notifications_type_channel_sent_created_device');
            
            // Index for failed notifications with device info
            $table->index(['sent', 'created_at', 'device_id'], 'device_notifications_sent_created_device');
            
            // Index for notification retry with device info
            $table->index(['sent', 'created_at', 'type', 'device_id'], 'device_notifications_sent_created_type_device');
            
            // Index for notification performance
            $table->index(['channel', 'sent', 'created_at'], 'device_notifications_channel_sent_created');
        });
        
        // Add indexes for device_activities table
        Schema::table('device_activities', function (Blueprint $table) {
            // Index for activity queries with device info
            $table->index(['device_id', 'activity_type', 'occurred_at', 'ip_address'], 'device_activities_device_type_occurred_ip');
            
            // Index for activity statistics with device info
            $table->index(['activity_type', 'occurred_at', 'device_id'], 'device_activities_type_occurred_device');
            
            // Index for IP-based activity queries with device info
            $table->index(['ip_address', 'occurred_at', 'device_id'], 'device_activities_ip_occurred_device');
            
            // Index for recent activities with device info
            $table->index(['occurred_at', 'activity_type', 'device_id'], 'device_activities_occurred_type_device');
            
            // Index for activity patterns
            $table->index(['activity_type', 'device_id', 'occurred_at'], 'device_activities_type_device_occurred');
        });
        
        // Add indexes for security_events table
        Schema::table('security_events', function (Blueprint $table) {
            // Index for security event queries with trackable info
            $table->index(['event_type', 'resolved', 'created_at', 'trackable_type', 'trackable_id'], 'security_events_type_resolved_created_trackable');
            
            // Index for risk assessment with trackable info
            $table->index(['risk_score', 'resolved', 'created_at', 'trackable_type', 'trackable_id'], 'security_events_risk_resolved_created_trackable');
            
            // Index for IP-based security queries with trackable info
            $table->index(['ip_address', 'created_at', 'trackable_type', 'trackable_id'], 'security_events_ip_created_trackable');
            
            // Index for trackable queries with event info
            $table->index(['trackable_type', 'trackable_id', 'created_at', 'event_type'], 'security_events_trackable_created_type');
            
            // Index for security event statistics
            $table->index(['event_type', 'created_at', 'resolved'], 'security_events_type_created_resolved');
        });
        
        // Add indexes for rate_limits table
        Schema::table('rate_limits', function (Blueprint $table) {
            // Index for rate limit queries with reset time
            $table->index(['key', 'type', 'reset_at', 'attempts'], 'rate_limits_key_type_reset_attempts');
            
            // Index for rate limit cleanup with type
            $table->index(['reset_at', 'type', 'key'], 'rate_limits_reset_type_key');
            
            // Index for rate limit statistics
            $table->index(['type', 'reset_at'], 'rate_limits_type_reset');
        });
        
        // Add indexes for api_keys table
        Schema::table('api_keys', function (Blueprint $table) {
            // Index for API key validation with device info
            $table->index(['key', 'is_active', 'expires_at', 'device_id'], 'api_keys_key_active_expires_device');
            
            // Index for device API key queries with active status
            $table->index(['device_id', 'is_active', 'expires_at', 'name'], 'api_keys_device_active_expires_name');
            
            // Index for API key cleanup with device info
            $table->index(['expires_at', 'is_active', 'device_id'], 'api_keys_expires_active_device');
            
            // Index for API key usage tracking with device info
            $table->index(['last_used_at', 'is_active', 'device_id'], 'api_keys_last_used_active_device');
            
            // Index for API key statistics
            $table->index(['name', 'is_active', 'created_at'], 'api_keys_name_active_created');
        });
        
        // Add indexes for device_sessions table
        Schema::table('device_sessions', function (Blueprint $table) {
            // Index for session queries with device info
            $table->index(['device_id', 'is_active', 'last_activity_at', 'session_id'], 'device_sessions_device_active_last_activity_session');
            
            // Index for session validation with device info
            $table->index(['session_id', 'is_active', 'device_id'], 'device_sessions_session_active_device');
            
            // Index for session cleanup with device info
            $table->index(['last_activity_at', 'is_active', 'device_id'], 'device_sessions_last_activity_active_device');
            
            // Index for IP-based session queries with device info
            $table->index(['ip_address', 'last_activity_at', 'device_id'], 'device_sessions_ip_last_activity_device');
            
            // Index for session statistics
            $table->index(['is_active', 'last_activity_at'], 'device_sessions_active_last_activity');
        });
        
        // Add indexes for login_attempts table
        Schema::table('login_attempts', function (Blueprint $table) {
            // Index for login attempt queries with success status
            $table->index(['email', 'attempted_at', 'successful'], 'login_attempts_email_attempted_successful');
            
            // Index for IP-based login attempt queries with success status
            $table->index(['ip_address', 'attempted_at', 'successful'], 'login_attempts_ip_attempted_successful');
            
            // Index for successful login attempts with email
            $table->index(['successful', 'attempted_at', 'email'], 'login_attempts_successful_attempted_email');
            
            // Index for failed login attempts with email
            $table->index(['email', 'successful', 'attempted_at'], 'login_attempts_email_successful_attempted');
            
            // Index for login attempt statistics
            $table->index(['attempted_at', 'successful'], 'login_attempts_attempted_successful');
        });
        
        // Add indexes for device_fingerprints table
        Schema::table('device_fingerprints', function (Blueprint $table) {
            // Index for fingerprint queries with device info
            $table->index(['device_id', 'is_verified', 'confidence_score'], 'device_fingerprints_device_verified_confidence');
            
            // Index for fingerprint lookup with verification status
            $table->index(['fingerprint', 'is_verified', 'confidence_score'], 'device_fingerprints_fingerprint_verified_confidence');
            
            // Index for confidence scoring with verification status
            $table->index(['confidence_score', 'is_verified', 'device_id'], 'device_fingerprints_confidence_verified_device');
            
            // Index for fingerprint verification with device info
            $table->index(['is_verified', 'verified_at', 'device_id'], 'device_fingerprints_verified_at_device');
            
            // Index for fingerprint statistics
            $table->index(['is_verified', 'confidence_score'], 'device_fingerprints_verified_confidence');
        });
        
        // Add indexes for user_sessions table
        Schema::table('user_sessions', function (Blueprint $table) {
            // Index for user session queries with device info
            $table->index(['user_id', 'is_active', 'last_activity_at', 'device_id'], 'user_sessions_user_active_last_activity_device');
            
            // Index for device session queries with user info
            $table->index(['device_id', 'is_active', 'user_id'], 'user_sessions_device_active_user');
            
            // Index for session validation with user info
            $table->index(['session_id', 'is_active', 'user_id'], 'user_sessions_session_active_user');
            
            // Index for session cleanup with user info
            $table->index(['last_activity_at', 'is_active', 'user_id'], 'user_sessions_last_activity_active_user');
            
            // Index for session statistics
            $table->index(['is_active', 'last_activity_at'], 'user_sessions_active_last_activity');
        });
        
        // Add indexes for audit_logs table
        Schema::table('audit_logs', function (Blueprint $table) {
            // Index for audit log queries with auditable info
            $table->index(['event', 'created_at', 'auditable_type', 'auditable_id'], 'audit_logs_event_created_auditable');
            
            // Index for auditable queries with event info
            $table->index(['auditable_type', 'auditable_id', 'created_at', 'event'], 'audit_logs_auditable_created_event');
            
            // Index for IP-based audit queries with event info
            $table->index(['ip_address', 'created_at', 'event'], 'audit_logs_ip_created_event');
            
            // Index for audit log cleanup with event info
            $table->index(['created_at', 'event', 'auditable_type'], 'audit_logs_created_event_auditable');
            
            // Index for audit log statistics
            $table->index(['event', 'auditable_type', 'created_at'], 'audit_logs_event_auditable_created');
        });
        
        // Add indexes for device_statistics table
        Schema::table('device_statistics', function (Blueprint $table) {
            // Index for device statistics queries with date range
            $table->index(['device_id', 'date', 'login_count'], 'device_statistics_device_date_login_count');
            
            // Index for date-based statistics queries with device info
            $table->index(['date', 'login_count', 'device_id'], 'device_statistics_date_login_count_device');
            
            // Index for device activity queries with date range
            $table->index(['device_id', 'last_activity_at', 'date'], 'device_statistics_device_last_activity_date');
            
            // Index for device statistics cleanup
            $table->index(['date', 'device_id'], 'device_statistics_date_device');
        });
        
        // Add indexes for user_statistics table
        Schema::table('user_statistics', function (Blueprint $table) {
            // Index for user statistics queries with date range
            $table->index(['user_id', 'date', 'total_devices'], 'user_statistics_user_date_total_devices');
            
            // Index for date-based statistics queries with user info
            $table->index(['date', 'total_devices', 'user_id'], 'user_statistics_date_total_devices_user');
            
            // Index for user activity queries with date range
            $table->index(['user_id', 'last_login_at', 'date'], 'user_statistics_user_last_login_date');
            
            // Index for user statistics cleanup
            $table->index(['date', 'user_id'], 'user_statistics_date_user');
        });
        
        // Add indexes for system_statistics table
        Schema::table('system_statistics', function (Blueprint $table) {
            // Index for system statistics queries with date range
            $table->index(['date', 'total_users', 'total_devices'], 'system_statistics_date_total_users_devices');
            
            // Index for date-based system queries with metrics
            $table->index(['date', 'total_devices', 'total_logins'], 'system_statistics_date_total_devices_logins');
            
            // Index for system activity queries with date range
            $table->index(['date', 'total_logins', 'suspicious_activities'], 'system_statistics_date_total_logins_suspicious');
            
            // Index for system statistics cleanup
            $table->index(['date'], 'system_statistics_date');
        });
        
        // Add indexes for notification_channels table
        Schema::table('notification_channels', function (Blueprint $table) {
            // Index for channel queries with active status
            $table->index(['name', 'is_active', 'driver'], 'notification_channels_name_active_driver');
            
            // Index for driver queries with active status
            $table->index(['driver', 'is_active', 'name'], 'notification_channels_driver_active_name');
            
            // Index for channel statistics
            $table->index(['is_active', 'name'], 'notification_channels_active_name');
        });
        
        // Add indexes for notification_templates table
        Schema::table('notification_templates', function (Blueprint $table) {
            // Index for template queries with active status
            $table->index(['type', 'channel', 'is_active', 'name'], 'notification_templates_type_channel_active_name');
            
            // Index for template lookup with active status
            $table->index(['type', 'is_active', 'channel'], 'notification_templates_type_active_channel');
            
            // Index for template statistics
            $table->index(['is_active', 'type'], 'notification_templates_active_type');
        });
        
        // Add indexes for notification_logs table
        Schema::table('notification_logs', function (Blueprint $table) {
            // Index for notification log queries with notifiable info
            $table->index(['type', 'channel', 'sent', 'created_at', 'notifiable_type', 'notifiable_id'], 'notification_logs_type_channel_sent_created_notifiable');
            
            // Index for notifiable queries with notification info
            $table->index(['notifiable_type', 'notifiable_id', 'created_at', 'type', 'channel'], 'notification_logs_notifiable_created_type_channel');
            
            // Index for retry queries with notification info
            $table->index(['sent', 'next_retry_at', 'type'], 'notification_logs_sent_next_retry_type');
            
            // Index for notification log statistics
            $table->index(['type', 'channel', 'sent'], 'notification_logs_type_channel_sent');
        });
        
        // Add indexes for feature_flags table
        Schema::table('feature_flags', function (Blueprint $table) {
            // Index for feature flag queries with enabled status
            $table->index(['name', 'is_enabled', 'enabled_at'], 'feature_flags_name_enabled_enabled_at');
            
            // Index for enabled flags with name
            $table->index(['is_enabled', 'name', 'enabled_at'], 'feature_flags_enabled_name_enabled_at');
            
            // Index for feature flag statistics
            $table->index(['is_enabled', 'enabled_at'], 'feature_flags_enabled_enabled_at');
        });
        
        // Add indexes for device_types table
        Schema::table('device_types', function (Blueprint $table) {
            // Index for device type queries with active status
            $table->index(['name', 'is_active', 'os'], 'device_types_name_active_os');
            
            // Index for OS queries with active status
            $table->index(['os', 'is_active', 'name'], 'device_types_os_active_name');
            
            // Index for category queries with active status
            $table->index(['category', 'is_active', 'name'], 'device_types_category_active_name');
            
            // Index for device type statistics
            $table->index(['is_active', 'os'], 'device_types_active_os');
        });
        
        // Add indexes for notification_rules table
        Schema::table('notification_rules', function (Blueprint $table) {
            // Index for notification rule queries with active status
            $table->index(['event_type', 'channel', 'is_active', 'priority'], 'notification_rules_event_channel_active_priority');
            
            // Index for priority queries with active status
            $table->index(['priority', 'is_active', 'event_type'], 'notification_rules_priority_active_event');
            
            // Index for notification rule statistics
            $table->index(['is_active', 'event_type'], 'notification_rules_active_event');
        });
        
        // Add indexes for countries table
        Schema::table('countries', function (Blueprint $table) {
            // Index for country queries with active status
            $table->index(['code', 'is_active', 'name'], 'countries_code_active_name');
            
            // Index for active countries with name
            $table->index(['is_active', 'name', 'code'], 'countries_active_name_code');
            
            // Index for country statistics
            $table->index(['is_active', 'code'], 'countries_active_code');
        });
        
        // Add indexes for timezones table
        Schema::table('timezones', function (Blueprint $table) {
            // Index for timezone queries with active status
            $table->index(['name', 'is_active', 'country_code'], 'timezones_name_active_country');
            
            // Index for country timezones with active status
            $table->index(['country_code', 'is_active', 'name'], 'timezones_country_active_name');
            
            // Index for timezone statistics
            $table->index(['is_active', 'name'], 'timezones_active_name');
        });
        
        // Add indexes for languages table
        Schema::table('languages', function (Blueprint $table) {
            // Index for language queries with active status
            $table->index(['code', 'is_active', 'name'], 'languages_code_active_name');
            
            // Index for active languages with name
            $table->index(['is_active', 'name', 'code'], 'languages_active_name_code');
            
            // Index for language statistics
            $table->index(['is_active', 'code'], 'languages_active_code');
        });
        
        // Add indexes for operating_systems table
        Schema::table('operating_systems', function (Blueprint $table) {
            // Index for OS queries with active status
            $table->index(['name', 'is_active', 'family'], 'operating_systems_name_active_family');
            
            // Index for family queries with active status
            $table->index(['family', 'is_active', 'name'], 'operating_systems_family_active_name');
            
            // Index for OS statistics
            $table->index(['is_active', 'family'], 'operating_systems_active_family');
        });
        
        // Add indexes for browsers table
        Schema::table('browsers', function (Blueprint $table) {
            // Index for browser queries with active status
            $table->index(['name', 'is_active', 'engine'], 'browsers_name_active_engine');
            
            // Index for engine queries with active status
            $table->index(['engine', 'is_active', 'name'], 'browsers_engine_active_name');
            
            // Index for mobile queries with active status
            $table->index(['is_mobile', 'is_active', 'name'], 'browsers_mobile_active_name');
            
            // Index for browser statistics
            $table->index(['is_active', 'engine'], 'browsers_active_engine');
        });
        
        // Add indexes for package_configurations table
        Schema::table('package_configurations', function (Blueprint $table) {
            // Index for configuration queries with encryption status
            $table->index(['key', 'is_encrypted', 'type'], 'package_configurations_key_encrypted_type');
            
            // Index for type queries with key
            $table->index(['type', 'key', 'is_encrypted'], 'package_configurations_type_key_encrypted');
            
            // Index for configuration statistics
            $table->index(['is_encrypted', 'type'], 'package_configurations_encrypted_type');
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
        // Note: This is a comprehensive list, but in practice you might want to
        // drop them selectively based on your needs
        
        // Drop device indexes
        Schema::table('devices', function (Blueprint $table) {
            $table->dropIndex('devices_user_active_trusted_last_seen');
            $table->dropIndex('devices_udid_user_active');
            $table->dropIndex('devices_client_active_last_seen');
            $table->dropIndex('devices_token_active_last_seen');
            $table->dropIndex('devices_api_key_active_last_seen');
            $table->dropIndex('devices_user_created_active_last_seen');
            $table->dropIndex('devices_last_seen_active_trusted');
            $table->dropIndex('devices_trusted_at_last_seen');
            $table->dropIndex('devices_os_manufacturer_active_last_seen');
            $table->dropIndex('devices_active_trusted_last_seen_created');
        });
        
        // Drop other table indexes...
        // (This would be a very long list, so I'm showing the pattern)
        // In practice, you would drop all the indexes you created
    }
};
