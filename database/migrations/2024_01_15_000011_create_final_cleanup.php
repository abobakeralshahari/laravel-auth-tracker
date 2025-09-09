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
        // Create stored procedures for data cleanup and maintenance
        
        // Procedure to clean up expired data
        DB::statement("
            CREATE PROCEDURE CleanupExpiredData()
            BEGIN
                DECLARE EXIT HANDLER FOR SQLEXCEPTION
                BEGIN
                    ROLLBACK;
                    RESIGNAL;
                END;
                
                START TRANSACTION;
                
                -- Clean up expired tokens
                DELETE FROM device_tokens 
                WHERE expires_at IS NOT NULL 
                AND expires_at < NOW() 
                AND is_active = 1;
                
                -- Clean up expired API keys
                DELETE FROM api_keys 
                WHERE expires_at IS NOT NULL 
                AND expires_at < NOW() 
                AND is_active = 1;
                
                -- Clean up old sessions (inactive for 30 days)
                DELETE FROM device_sessions 
                WHERE last_activity_at < DATE_SUB(NOW(), INTERVAL 30 DAY) 
                AND is_active = 0;
                
                -- Clean up old user sessions (inactive for 30 days)
                DELETE FROM user_sessions 
                WHERE last_activity_at < DATE_SUB(NOW(), INTERVAL 30 DAY) 
                AND is_active = 0;
                
                -- Clean up old audit logs (older than 90 days)
                DELETE FROM audit_logs 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
                
                -- Clean up old login attempts (older than 30 days)
                DELETE FROM login_attempts 
                WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
                
                -- Clean up old security events (older than 180 days)
                DELETE FROM security_events 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL 180 DAY);
                
                -- Clean up old device activities (older than 90 days)
                DELETE FROM device_activities 
                WHERE occurred_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
                
                -- Clean up old notification logs (older than 30 days)
                DELETE FROM notification_logs 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
                
                COMMIT;
            END
        ");
        
        // Procedure to clean up inactive devices
        DB::statement("
            CREATE PROCEDURE CleanupInactiveDevices()
            BEGIN
                DECLARE EXIT HANDLER FOR SQLEXCEPTION
                BEGIN
                    ROLLBACK;
                    RESIGNAL;
                END;
                
                START TRANSACTION;
                
                -- Mark devices as inactive if not seen for 90 days
                UPDATE devices 
                SET is_active = 0 
                WHERE last_seen_at < DATE_SUB(NOW(), INTERVAL 90 DAY) 
                AND is_active = 1;
                
                -- Soft delete devices that haven't been seen for 180 days
                UPDATE devices 
                SET deleted_at = NOW() 
                WHERE last_seen_at < DATE_SUB(NOW(), INTERVAL 180 DAY) 
                AND deleted_at IS NULL;
                
                -- Hard delete devices that have been soft deleted for 30 days
                DELETE FROM devices 
                WHERE deleted_at IS NOT NULL 
                AND deleted_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
                
                COMMIT;
            END
        ");
        
        // Procedure to clean up old statistics
        DB::statement("
            CREATE PROCEDURE CleanupOldStatistics()
            BEGIN
                DECLARE EXIT HANDLER FOR SQLEXCEPTION
                BEGIN
                    ROLLBACK;
                    RESIGNAL;
                END;
                
                START TRANSACTION;
                
                -- Clean up device statistics older than 1 year
                DELETE FROM device_statistics 
                WHERE date < DATE_SUB(NOW(), INTERVAL 1 YEAR);
                
                -- Clean up user statistics older than 1 year
                DELETE FROM user_statistics 
                WHERE date < DATE_SUB(NOW(), INTERVAL 1 YEAR);
                
                -- Clean up system statistics older than 1 year
                DELETE FROM system_statistics 
                WHERE date < DATE_SUB(NOW(), INTERVAL 1 YEAR);
                
                COMMIT;
            END
        ");
        
        // Procedure to optimize database
        DB::statement("
            CREATE PROCEDURE OptimizeDatabase()
            BEGIN
                DECLARE EXIT HANDLER FOR SQLEXCEPTION
                BEGIN
                    ROLLBACK;
                    RESIGNAL;
                END;
                
                START TRANSACTION;
                
                -- Optimize main tables
                OPTIMIZE TABLE devices;
                OPTIMIZE TABLE logins;
                OPTIMIZE TABLE device_tokens;
                OPTIMIZE TABLE device_notifications;
                OPTIMIZE TABLE device_activities;
                OPTIMIZE TABLE security_events;
                OPTIMIZE TABLE rate_limits;
                OPTIMIZE TABLE api_keys;
                OPTIMIZE TABLE device_sessions;
                OPTIMIZE TABLE login_attempts;
                OPTIMIZE TABLE device_fingerprints;
                OPTIMIZE TABLE user_sessions;
                OPTIMIZE TABLE audit_logs;
                OPTIMIZE TABLE device_statistics;
                OPTIMIZE TABLE user_statistics;
                OPTIMIZE TABLE system_statistics;
                OPTIMIZE TABLE notification_channels;
                OPTIMIZE TABLE notification_templates;
                OPTIMIZE TABLE notification_logs;
                OPTIMIZE TABLE feature_flags;
                OPTIMIZE TABLE device_types;
                OPTIMIZE TABLE notification_rules;
                OPTIMIZE TABLE countries;
                OPTIMIZE TABLE timezones;
                OPTIMIZE TABLE languages;
                OPTIMIZE TABLE operating_systems;
                OPTIMIZE TABLE browsers;
                OPTIMIZE TABLE package_configurations;
                
                COMMIT;
            END
        ");
        
        // Procedure to analyze database performance
        DB::statement("
            CREATE PROCEDURE AnalyzeDatabasePerformance()
            BEGIN
                DECLARE EXIT HANDLER FOR SQLEXCEPTION
                BEGIN
                    ROLLBACK;
                    RESIGNAL;
                END;
                
                START TRANSACTION;
                
                -- Analyze main tables
                ANALYZE TABLE devices;
                ANALYZE TABLE logins;
                ANALYZE TABLE device_tokens;
                ANALYZE TABLE device_notifications;
                ANALYZE TABLE device_activities;
                ANALYZE TABLE security_events;
                ANALYZE TABLE rate_limits;
                ANALYZE TABLE api_keys;
                ANALYZE TABLE device_sessions;
                ANALYZE TABLE login_attempts;
                ANALYZE TABLE device_fingerprints;
                ANALYZE TABLE user_sessions;
                ANALYZE TABLE audit_logs;
                ANALYZE TABLE device_statistics;
                ANALYZE TABLE user_statistics;
                ANALYZE TABLE system_statistics;
                ANALYZE TABLE notification_channels;
                ANALYZE TABLE notification_templates;
                ANALYZE TABLE notification_logs;
                ANALYZE TABLE feature_flags;
                ANALYZE TABLE device_types;
                ANALYZE TABLE notification_rules;
                ANALYZE TABLE countries;
                ANALYZE TABLE timezones;
                ANALYZE TABLE languages;
                ANALYZE TABLE operating_systems;
                ANALYZE TABLE browsers;
                ANALYZE TABLE package_configurations;
                
                COMMIT;
            END
        ");
        
        // Procedure to check database health
        DB::statement("
            CREATE PROCEDURE CheckDatabaseHealth()
            BEGIN
                DECLARE EXIT HANDLER FOR SQLEXCEPTION
                BEGIN
                    ROLLBACK;
                    RESIGNAL;
                END;
                
                START TRANSACTION;
                
                -- Check for orphaned records
                SELECT 'Orphaned device tokens' as check_type, COUNT(*) as count
                FROM device_tokens dt
                LEFT JOIN devices d ON dt.device_id = d.id
                WHERE d.id IS NULL;
                
                SELECT 'Orphaned device notifications' as check_type, COUNT(*) as count
                FROM device_notifications dn
                LEFT JOIN devices d ON dn.device_id = d.id
                WHERE d.id IS NULL;
                
                SELECT 'Orphaned device activities' as check_type, COUNT(*) as count
                FROM device_activities da
                LEFT JOIN devices d ON da.device_id = d.id
                WHERE d.id IS NULL;
                
                SELECT 'Orphaned API keys' as check_type, COUNT(*) as count
                FROM api_keys ak
                LEFT JOIN devices d ON ak.device_id = d.id
                WHERE d.id IS NULL;
                
                SELECT 'Orphaned device sessions' as check_type, COUNT(*) as count
                FROM device_sessions ds
                LEFT JOIN devices d ON ds.device_id = d.id
                WHERE d.id IS NULL;
                
                SELECT 'Orphaned device fingerprints' as check_type, COUNT(*) as count
                FROM device_fingerprints df
                LEFT JOIN devices d ON df.device_id = d.id
                WHERE d.id IS NULL;
                
                SELECT 'Orphaned user sessions' as check_type, COUNT(*) as count
                FROM user_sessions us
                LEFT JOIN users u ON us.user_id = u.id
                WHERE u.id IS NULL;
                
                SELECT 'Orphaned device statistics' as check_type, COUNT(*) as count
                FROM device_statistics dst
                LEFT JOIN devices d ON dst.device_id = d.id
                WHERE d.id IS NULL;
                
                SELECT 'Orphaned user statistics' as check_type, COUNT(*) as count
                FROM user_statistics ust
                LEFT JOIN users u ON ust.user_id = u.id
                WHERE u.id IS NULL;
                
                -- Check for data consistency
                SELECT 'Inconsistent device data' as check_type, COUNT(*) as count
                FROM devices d
                WHERE d.user_id IS NULL OR d.udid IS NULL;
                
                SELECT 'Inconsistent login data' as check_type, COUNT(*) as count
                FROM logins l
                WHERE l.user_id IS NULL;
                
                SELECT 'Inconsistent security events' as check_type, COUNT(*) as count
                FROM security_events se
                WHERE se.trackable_id IS NULL OR se.trackable_type IS NULL;
                
                COMMIT;
            END
        ");
        
        // Procedure to repair database
        DB::statement("
            CREATE PROCEDURE RepairDatabase()
            BEGIN
                DECLARE EXIT HANDLER FOR SQLEXCEPTION
                BEGIN
                    ROLLBACK;
                    RESIGNAL;
                END;
                
                START TRANSACTION;
                
                -- Repair main tables
                REPAIR TABLE devices;
                REPAIR TABLE logins;
                REPAIR TABLE device_tokens;
                REPAIR TABLE device_notifications;
                REPAIR TABLE device_activities;
                REPAIR TABLE security_events;
                REPAIR TABLE rate_limits;
                REPAIR TABLE api_keys;
                REPAIR TABLE device_sessions;
                REPAIR TABLE login_attempts;
                REPAIR TABLE device_fingerprints;
                REPAIR TABLE user_sessions;
                REPAIR TABLE audit_logs;
                REPAIR TABLE device_statistics;
                REPAIR TABLE user_statistics;
                REPAIR TABLE system_statistics;
                REPAIR TABLE notification_channels;
                REPAIR TABLE notification_templates;
                REPAIR TABLE notification_logs;
                REPAIR TABLE feature_flags;
                REPAIR TABLE device_types;
                REPAIR TABLE notification_rules;
                REPAIR TABLE countries;
                REPAIR TABLE timezones;
                REPAIR TABLE languages;
                REPAIR TABLE operating_systems;
                REPAIR TABLE browsers;
                REPAIR TABLE package_configurations;
                
                COMMIT;
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
        // Drop stored procedures
        DB::statement("DROP PROCEDURE IF EXISTS CleanupExpiredData");
        DB::statement("DROP PROCEDURE IF EXISTS CleanupInactiveDevices");
        DB::statement("DROP PROCEDURE IF EXISTS CleanupOldStatistics");
        DB::statement("DROP PROCEDURE IF EXISTS OptimizeDatabase");
        DB::statement("DROP PROCEDURE IF EXISTS AnalyzeDatabasePerformance");
        DB::statement("DROP PROCEDURE IF EXISTS CheckDatabaseHealth");
        DB::statement("DROP PROCEDURE IF EXISTS RepairDatabase");
    }
};
