<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Create views for common queries
        
        // Active devices view
        DB::statement("
            CREATE VIEW active_devices AS
            SELECT 
                d.*,
                u.name as user_name,
                u.email as user_email,
                COUNT(l.id) as total_logins,
                MAX(l.created_at) as last_login_at
            FROM devices d
            LEFT JOIN users u ON d.user_id = u.id
            LEFT JOIN logins l ON d.id = l.device_id
            WHERE d.is_active = 1 AND d.deleted_at IS NULL
            GROUP BY d.id, u.id
        ");
        
        // Device statistics view
        DB::statement("
            CREATE VIEW device_statistics_summary AS
            SELECT 
                d.id as device_id,
                d.user_id,
                d.udid,
                d.os,
                d.model,
                d.manufacturer,
                d.is_trusted,
                d.created_at as device_created_at,
                COUNT(l.id) as total_logins,
                COUNT(CASE WHEN l.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as logins_last_30_days,
                COUNT(CASE WHEN l.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as logins_last_7_days,
                MAX(l.created_at) as last_login_at,
                MIN(l.created_at) as first_login_at
            FROM devices d
            LEFT JOIN logins l ON d.id = l.device_id
            WHERE d.deleted_at IS NULL
            GROUP BY d.id, d.user_id, d.udid, d.os, d.model, d.manufacturer, d.is_trusted, d.created_at
        ");
        
        // User device summary view
        DB::statement("
            CREATE VIEW user_device_summary AS
            SELECT 
                u.id as user_id,
                u.name as user_name,
                u.email as user_email,
                COUNT(d.id) as total_devices,
                COUNT(CASE WHEN d.is_active = 1 THEN 1 END) as active_devices,
                COUNT(CASE WHEN d.is_trusted = 1 THEN 1 END) as trusted_devices,
                COUNT(CASE WHEN d.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_devices_last_30_days,
                MAX(d.last_seen_at) as last_device_activity,
                MAX(l.created_at) as last_login_at
            FROM users u
            LEFT JOIN devices d ON u.id = d.user_id AND d.deleted_at IS NULL
            LEFT JOIN logins l ON u.id = l.user_id
            GROUP BY u.id, u.name, u.email
        ");
        
        // Security events summary view
        DB::statement("
            CREATE VIEW security_events_summary AS
            SELECT 
                se.event_type,
                COUNT(*) as total_events,
                COUNT(CASE WHEN se.resolved = 0 THEN 1 END) as unresolved_events,
                COUNT(CASE WHEN se.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as events_last_24_hours,
                COUNT(CASE WHEN se.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as events_last_7_days,
                AVG(se.risk_score) as average_risk_score,
                MAX(se.risk_score) as max_risk_score,
                MAX(se.created_at) as last_event_at
            FROM security_events se
            GROUP BY se.event_type
        ");
        
        // Notification statistics view
        DB::statement("
            CREATE VIEW notification_statistics AS
            SELECT 
                dn.type as notification_type,
                dn.channel,
                COUNT(*) as total_notifications,
                COUNT(CASE WHEN dn.sent = 1 THEN 1 END) as sent_notifications,
                COUNT(CASE WHEN dn.sent = 0 THEN 1 END) as failed_notifications,
                COUNT(CASE WHEN dn.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as notifications_last_24_hours,
                COUNT(CASE WHEN dn.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as notifications_last_7_days,
                AVG(CASE WHEN dn.sent = 1 THEN TIMESTAMPDIFF(SECOND, dn.created_at, dn.sent_at) END) as average_delivery_time_seconds
            FROM device_notifications dn
            GROUP BY dn.type, dn.channel
        ");
        
        // Create stored procedures for common operations
        
        // Procedure to clean up expired tokens
        DB::statement("
            CREATE PROCEDURE CleanupExpiredTokens()
            BEGIN
                DELETE FROM device_tokens 
                WHERE expires_at IS NOT NULL 
                AND expires_at < NOW() 
                AND is_active = 1;
                
                UPDATE device_tokens 
                SET is_active = 0 
                WHERE expires_at IS NOT NULL 
                AND expires_at < NOW() 
                AND is_active = 1;
            END
        ");
        
        // Procedure to clean up old sessions
        DB::statement("
            CREATE PROCEDURE CleanupOldSessions()
            BEGIN
                DELETE FROM device_sessions 
                WHERE last_activity_at < DATE_SUB(NOW(), INTERVAL 30 DAY) 
                AND is_active = 0;
                
                UPDATE device_sessions 
                SET is_active = 0 
                WHERE last_activity_at < DATE_SUB(NOW(), INTERVAL 7 DAY) 
                AND is_active = 1;
            END
        ");
        
        // Procedure to clean up old audit logs
        DB::statement("
            CREATE PROCEDURE CleanupOldAuditLogs()
            BEGIN
                DELETE FROM audit_logs 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
            END
        ");
        
        // Procedure to update device statistics
        DB::statement("
            CREATE PROCEDURE UpdateDeviceStatistics(IN device_id INT, IN stat_date DATE)
            BEGIN
                INSERT INTO device_statistics (
                    device_id, 
                    date, 
                    login_count, 
                    logout_count, 
                    api_calls_count, 
                    notification_sent_count, 
                    error_count, 
                    first_activity_at, 
                    last_activity_at,
                    created_at,
                    updated_at
                )
                SELECT 
                    device_id,
                    stat_date,
                    COUNT(CASE WHEN la.activity_type = 'login' THEN 1 END) as login_count,
                    COUNT(CASE WHEN la.activity_type = 'logout' THEN 1 END) as logout_count,
                    COUNT(CASE WHEN la.activity_type = 'api_call' THEN 1 END) as api_calls_count,
                    COUNT(CASE WHEN dn.type = 'new_device' OR dn.type = 'suspicious_login' THEN 1 END) as notification_sent_count,
                    COUNT(CASE WHEN la.activity_type = 'error' THEN 1 END) as error_count,
                    MIN(la.occurred_at) as first_activity_at,
                    MAX(la.occurred_at) as last_activity_at,
                    NOW(),
                    NOW()
                FROM device_activities la
                LEFT JOIN device_notifications dn ON la.device_id = dn.device_id
                WHERE la.device_id = device_id 
                AND DATE(la.occurred_at) = stat_date
                ON DUPLICATE KEY UPDATE
                    login_count = VALUES(login_count),
                    logout_count = VALUES(logout_count),
                    api_calls_count = VALUES(api_calls_count),
                    notification_sent_count = VALUES(notification_sent_count),
                    error_count = VALUES(error_count),
                    first_activity_at = VALUES(first_activity_at),
                    last_activity_at = VALUES(last_activity_at),
                    updated_at = NOW();
            END
        ");
        
        // Procedure to update user statistics
        DB::statement("
            CREATE PROCEDURE UpdateUserStatistics(IN user_id INT, IN stat_date DATE)
            BEGIN
                INSERT INTO user_statistics (
                    user_id, 
                    date, 
                    total_devices, 
                    active_devices, 
                    trusted_devices, 
                    login_count, 
                    suspicious_activities, 
                    first_login_at, 
                    last_login_at,
                    created_at,
                    updated_at
                )
                SELECT 
                    user_id,
                    stat_date,
                    COUNT(d.id) as total_devices,
                    COUNT(CASE WHEN d.is_active = 1 THEN 1 END) as active_devices,
                    COUNT(CASE WHEN d.is_trusted = 1 THEN 1 END) as trusted_devices,
                    COUNT(l.id) as login_count,
                    COUNT(se.id) as suspicious_activities,
                    MIN(l.created_at) as first_login_at,
                    MAX(l.created_at) as last_login_at,
                    NOW(),
                    NOW()
                FROM users u
                LEFT JOIN devices d ON u.id = d.user_id AND d.deleted_at IS NULL
                LEFT JOIN logins l ON u.id = l.user_id AND DATE(l.created_at) = stat_date
                LEFT JOIN security_events se ON u.id = se.trackable_id AND se.trackable_type = 'App\\Models\\User' AND DATE(se.created_at) = stat_date
                WHERE u.id = user_id
                ON DUPLICATE KEY UPDATE
                    total_devices = VALUES(total_devices),
                    active_devices = VALUES(active_devices),
                    trusted_devices = VALUES(trusted_devices),
                    login_count = VALUES(login_count),
                    suspicious_activities = VALUES(suspicious_activities),
                    first_login_at = VALUES(first_login_at),
                    last_login_at = VALUES(last_login_at),
                    updated_at = NOW();
            END
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop views
        DB::statement("DROP VIEW IF EXISTS active_devices");
        DB::statement("DROP VIEW IF EXISTS device_statistics_summary");
        DB::statement("DROP VIEW IF EXISTS user_device_summary");
        DB::statement("DROP VIEW IF EXISTS security_events_summary");
        DB::statement("DROP VIEW IF EXISTS notification_statistics");
        
        // Drop stored procedures
        DB::statement("DROP PROCEDURE IF EXISTS CleanupExpiredTokens");
        DB::statement("DROP PROCEDURE IF EXISTS CleanupOldSessions");
        DB::statement("DROP PROCEDURE IF EXISTS CleanupOldAuditLogs");
        DB::statement("DROP PROCEDURE IF EXISTS UpdateDeviceStatistics");
        DB::statement("DROP PROCEDURE IF EXISTS UpdateUserStatistics");
    }
};
