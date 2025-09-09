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
        // Create additional views for common queries and reporting
        
        // Device security overview view
        DB::statement("
            CREATE VIEW device_security_overview AS
            SELECT 
                d.id as device_id,
                d.user_id,
                d.udid,
                d.os,
                d.model,
                d.manufacturer,
                d.is_trusted,
                d.is_active,
                d.client_id,
                d.last_seen_at,
                d.created_at as device_created_at,
                u.name as user_name,
                u.email as user_email,
                COUNT(l.id) as total_logins,
                COUNT(CASE WHEN l.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as logins_last_30_days,
                COUNT(se.id) as security_events,
                COUNT(CASE WHEN se.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as security_events_last_7_days,
                MAX(se.risk_score) as max_risk_score,
                AVG(se.risk_score) as avg_risk_score,
                CASE 
                    WHEN d.is_trusted = 1 THEN 'Trusted'
                    WHEN COUNT(se.id) > 0 THEN 'Suspicious'
                    WHEN d.last_seen_at < DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 'Inactive'
                    ELSE 'Normal'
                END as security_status
            FROM devices d
            LEFT JOIN users u ON d.user_id = u.id
            LEFT JOIN logins l ON d.id = l.device_id
            LEFT JOIN security_events se ON d.id = se.trackable_id AND se.trackable_type = 'App\\Models\\Device'
            WHERE d.deleted_at IS NULL
            GROUP BY d.id, d.user_id, d.udid, d.os, d.model, d.manufacturer, d.is_trusted, d.is_active, d.client_id, d.last_seen_at, d.created_at, u.name, u.email
        ");
        
        // User activity summary view
        DB::statement("
            CREATE VIEW user_activity_summary AS
            SELECT 
                u.id as user_id,
                u.name as user_name,
                u.email as user_email,
                u.created_at as user_created_at,
                COUNT(DISTINCT d.id) as total_devices,
                COUNT(DISTINCT CASE WHEN d.is_active = 1 THEN d.id END) as active_devices,
                COUNT(DISTINCT CASE WHEN d.is_trusted = 1 THEN d.id END) as trusted_devices,
                COUNT(l.id) as total_logins,
                COUNT(CASE WHEN l.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as logins_last_30_days,
                COUNT(CASE WHEN l.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as logins_last_7_days,
                MAX(l.created_at) as last_login_at,
                MIN(l.created_at) as first_login_at,
                COUNT(se.id) as security_events,
                COUNT(CASE WHEN se.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as security_events_last_7_days,
                MAX(se.risk_score) as max_risk_score,
                AVG(se.risk_score) as avg_risk_score,
                CASE 
                    WHEN COUNT(se.id) > 0 THEN 'High Risk'
                    WHEN COUNT(DISTINCT d.id) > 5 THEN 'Medium Risk'
                    ELSE 'Low Risk'
                END as risk_level
            FROM users u
            LEFT JOIN devices d ON u.id = d.user_id AND d.deleted_at IS NULL
            LEFT JOIN logins l ON u.id = l.user_id
            LEFT JOIN security_events se ON u.id = se.trackable_id AND se.trackable_type = 'App\\Models\\User'
            GROUP BY u.id, u.name, u.email, u.created_at
        ");
        
        // System performance overview view
        DB::statement("
            CREATE VIEW system_performance_overview AS
            SELECT 
                DATE(created_at) as date,
                COUNT(DISTINCT u.id) as total_users,
                COUNT(DISTINCT d.id) as total_devices,
                COUNT(DISTINCT CASE WHEN d.is_active = 1 THEN d.id END) as active_devices,
                COUNT(DISTINCT CASE WHEN d.is_trusted = 1 THEN d.id END) as trusted_devices,
                COUNT(l.id) as total_logins,
                COUNT(CASE WHEN l.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as logins_last_24_hours,
                COUNT(se.id) as security_events,
                COUNT(CASE WHEN se.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as security_events_last_24_hours,
                COUNT(dn.id) as notifications_sent,
                COUNT(CASE WHEN dn.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as notifications_sent_last_24_hours,
                AVG(se.risk_score) as avg_risk_score,
                MAX(se.risk_score) as max_risk_score
            FROM users u
            LEFT JOIN devices d ON u.id = d.user_id AND d.deleted_at IS NULL
            LEFT JOIN logins l ON u.id = l.user_id
            LEFT JOIN security_events se ON u.id = se.trackable_id AND se.trackable_type = 'App\\Models\\User'
            LEFT JOIN device_notifications dn ON d.id = dn.device_id
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");
        
        // Device type statistics view
        DB::statement("
            CREATE VIEW device_type_statistics AS
            SELECT 
                d.os,
                d.manufacturer,
                d.model,
                COUNT(*) as device_count,
                COUNT(CASE WHEN d.is_active = 1 THEN 1 END) as active_count,
                COUNT(CASE WHEN d.is_trusted = 1 THEN 1 END) as trusted_count,
                COUNT(l.id) as total_logins,
                COUNT(CASE WHEN l.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as logins_last_30_days,
                AVG(TIMESTAMPDIFF(DAY, d.created_at, NOW())) as avg_device_age_days,
                MAX(d.last_seen_at) as last_activity,
                COUNT(se.id) as security_events,
                AVG(se.risk_score) as avg_risk_score
            FROM devices d
            LEFT JOIN logins l ON d.id = l.device_id
            LEFT JOIN security_events se ON d.id = se.trackable_id AND se.trackable_type = 'App\\Models\\Device'
            WHERE d.deleted_at IS NULL
            GROUP BY d.os, d.manufacturer, d.model
            ORDER BY device_count DESC
        ");
        
        // Notification effectiveness view
        DB::statement("
            CREATE VIEW notification_effectiveness AS
            SELECT 
                dn.type as notification_type,
                dn.channel,
                COUNT(*) as total_notifications,
                COUNT(CASE WHEN dn.sent = 1 THEN 1 END) as sent_notifications,
                COUNT(CASE WHEN dn.sent = 0 THEN 1 END) as failed_notifications,
                ROUND((COUNT(CASE WHEN dn.sent = 1 THEN 1 END) / COUNT(*)) * 100, 2) as success_rate,
                AVG(CASE WHEN dn.sent = 1 THEN TIMESTAMPDIFF(SECOND, dn.created_at, dn.sent_at) END) as avg_delivery_time_seconds,
                COUNT(CASE WHEN dn.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as notifications_last_24_hours,
                COUNT(CASE WHEN dn.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as notifications_last_7_days,
                COUNT(CASE WHEN dn.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as notifications_last_30_days
            FROM device_notifications dn
            GROUP BY dn.type, dn.channel
            ORDER BY total_notifications DESC
        ");
        
        // Security events timeline view
        DB::statement("
            CREATE VIEW security_events_timeline AS
            SELECT 
                DATE(se.created_at) as date,
                se.event_type,
                COUNT(*) as event_count,
                AVG(se.risk_score) as avg_risk_score,
                MAX(se.risk_score) as max_risk_score,
                COUNT(CASE WHEN se.resolved = 1 THEN 1 END) as resolved_events,
                COUNT(CASE WHEN se.resolved = 0 THEN 1 END) as unresolved_events,
                ROUND((COUNT(CASE WHEN se.resolved = 1 THEN 1 END) / COUNT(*)) * 100, 2) as resolution_rate
            FROM security_events se
            GROUP BY DATE(se.created_at), se.event_type
            ORDER BY date DESC, event_count DESC
        ");
        
        // Device activity patterns view
        DB::statement("
            CREATE VIEW device_activity_patterns AS
            SELECT 
                d.id as device_id,
                d.user_id,
                d.os,
                d.model,
                d.manufacturer,
                HOUR(da.occurred_at) as hour_of_day,
                DAYOFWEEK(da.occurred_at) as day_of_week,
                da.activity_type,
                COUNT(*) as activity_count,
                COUNT(DISTINCT DATE(da.occurred_at)) as active_days
            FROM devices d
            JOIN device_activities da ON d.id = da.device_id
            WHERE d.deleted_at IS NULL
            AND da.occurred_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY d.id, d.user_id, d.os, d.model, d.manufacturer, HOUR(da.occurred_at), DAYOFWEEK(da.occurred_at), da.activity_type
            ORDER BY activity_count DESC
        ");
        
        // API usage statistics view
        DB::statement("
            CREATE VIEW api_usage_statistics AS
            SELECT 
                ak.device_id,
                d.user_id,
                d.os,
                d.model,
                ak.name as api_key_name,
                COUNT(da.id) as api_calls,
                COUNT(CASE WHEN da.occurred_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as api_calls_last_24_hours,
                COUNT(CASE WHEN da.occurred_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as api_calls_last_7_days,
                MAX(da.occurred_at) as last_api_call,
                MIN(da.occurred_at) as first_api_call,
                ak.created_at as api_key_created_at,
                ak.last_used_at,
                ak.expires_at
            FROM api_keys ak
            JOIN devices d ON ak.device_id = d.id
            LEFT JOIN device_activities da ON d.id = da.device_id AND da.activity_type = 'api_call'
            WHERE ak.is_active = 1
            AND d.deleted_at IS NULL
            GROUP BY ak.device_id, d.user_id, d.os, d.model, ak.name, ak.created_at, ak.last_used_at, ak.expires_at
            ORDER BY api_calls DESC
        ");
        
        // User device distribution view
        DB::statement("
            CREATE VIEW user_device_distribution AS
            SELECT 
                u.id as user_id,
                u.name as user_name,
                u.email as user_email,
                COUNT(DISTINCT d.id) as total_devices,
                COUNT(DISTINCT CASE WHEN d.os = 'iOS' THEN d.id END) as ios_devices,
                COUNT(DISTINCT CASE WHEN d.os = 'Android' THEN d.id END) as android_devices,
                COUNT(DISTINCT CASE WHEN d.os = 'Windows' THEN d.id END) as windows_devices,
                COUNT(DISTINCT CASE WHEN d.os = 'macOS' THEN d.id END) as macos_devices,
                COUNT(DISTINCT CASE WHEN d.os = 'Linux' THEN d.id END) as linux_devices,
                COUNT(DISTINCT CASE WHEN d.is_trusted = 1 THEN d.id END) as trusted_devices,
                COUNT(DISTINCT CASE WHEN d.is_active = 1 THEN d.id END) as active_devices,
                MAX(d.last_seen_at) as last_device_activity,
                MIN(d.created_at) as first_device_created
            FROM users u
            LEFT JOIN devices d ON u.id = d.user_id AND d.deleted_at IS NULL
            GROUP BY u.id, u.name, u.email
            ORDER BY total_devices DESC
        ");
        
        // System health metrics view
        DB::statement("
            CREATE VIEW system_health_metrics AS
            SELECT 
                'Total Users' as metric_name,
                COUNT(*) as metric_value,
                NOW() as recorded_at
            FROM users
            UNION ALL
            SELECT 
                'Total Devices' as metric_name,
                COUNT(*) as metric_value,
                NOW() as recorded_at
            FROM devices
            WHERE deleted_at IS NULL
            UNION ALL
            SELECT 
                'Active Devices' as metric_name,
                COUNT(*) as metric_value,
                NOW() as recorded_at
            FROM devices
            WHERE is_active = 1 AND deleted_at IS NULL
            UNION ALL
            SELECT 
                'Trusted Devices' as metric_name,
                COUNT(*) as metric_value,
                NOW() as recorded_at
            FROM devices
            WHERE is_trusted = 1 AND deleted_at IS NULL
            UNION ALL
            SELECT 
                'Total Logins (24h)' as metric_name,
                COUNT(*) as metric_value,
                NOW() as recorded_at
            FROM logins
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            UNION ALL
            SELECT 
                'Security Events (24h)' as metric_name,
                COUNT(*) as metric_value,
                NOW() as recorded_at
            FROM security_events
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            UNION ALL
            SELECT 
                'Notifications Sent (24h)' as metric_name,
                COUNT(*) as metric_value,
                NOW() as recorded_at
            FROM device_notifications
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            UNION ALL
            SELECT 
                'Failed Notifications (24h)' as metric_name,
                COUNT(*) as metric_value,
                NOW() as recorded_at
            FROM device_notifications
            WHERE sent = 0 AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        
        // Device risk assessment view
        DB::statement("
            CREATE VIEW device_risk_assessment AS
            SELECT 
                d.id as device_id,
                d.user_id,
                d.udid,
                d.os,
                d.model,
                d.manufacturer,
                d.is_trusted,
                d.is_active,
                d.last_seen_at,
                d.created_at as device_created_at,
                u.name as user_name,
                u.email as user_email,
                COUNT(l.id) as total_logins,
                COUNT(CASE WHEN l.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as logins_last_30_days,
                COUNT(se.id) as security_events,
                COUNT(CASE WHEN se.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as security_events_last_7_days,
                MAX(se.risk_score) as max_risk_score,
                AVG(se.risk_score) as avg_risk_score,
                DATEDIFF(NOW(), d.created_at) as device_age_days,
                DATEDIFF(NOW(), d.last_seen_at) as days_since_last_seen,
                CASE 
                    WHEN d.is_trusted = 1 THEN 0
                    WHEN COUNT(se.id) > 0 THEN 25
                    WHEN DATEDIFF(NOW(), d.created_at) < 7 THEN 20
                    WHEN COUNT(l.id) < 5 THEN 15
                    WHEN DATEDIFF(NOW(), d.last_seen_at) > 30 THEN 10
                    ELSE 5
                END as calculated_risk_score,
                CASE 
                    WHEN d.is_trusted = 1 THEN 'Low Risk'
                    WHEN COUNT(se.id) > 0 THEN 'High Risk'
                    WHEN DATEDIFF(NOW(), d.created_at) < 7 THEN 'Medium Risk'
                    WHEN COUNT(l.id) < 5 THEN 'Medium Risk'
                    WHEN DATEDIFF(NOW(), d.last_seen_at) > 30 THEN 'Low Risk'
                    ELSE 'Low Risk'
                END as risk_level
            FROM devices d
            LEFT JOIN users u ON d.user_id = u.id
            LEFT JOIN logins l ON d.id = l.device_id
            LEFT JOIN security_events se ON d.id = se.trackable_id AND se.trackable_type = 'App\\Models\\Device'
            WHERE d.deleted_at IS NULL
            GROUP BY d.id, d.user_id, d.udid, d.os, d.model, d.manufacturer, d.is_trusted, d.is_active, d.last_seen_at, d.created_at, u.name, u.email
            ORDER BY calculated_risk_score DESC, security_events DESC
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
        DB::statement("DROP VIEW IF EXISTS device_security_overview");
        DB::statement("DROP VIEW IF EXISTS user_activity_summary");
        DB::statement("DROP VIEW IF EXISTS system_performance_overview");
        DB::statement("DROP VIEW IF EXISTS device_type_statistics");
        DB::statement("DROP VIEW IF EXISTS notification_effectiveness");
        DB::statement("DROP VIEW IF EXISTS security_events_timeline");
        DB::statement("DROP VIEW IF EXISTS device_activity_patterns");
        DB::statement("DROP VIEW IF EXISTS api_usage_statistics");
        DB::statement("DROP VIEW IF EXISTS user_device_distribution");
        DB::statement("DROP VIEW IF EXISTS system_health_metrics");
        DB::statement("DROP VIEW IF EXISTS device_risk_assessment");
    }
};
