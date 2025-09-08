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
        // Create triggers for automatic data management
        
        // Trigger to update device last_seen_at on login
        DB::statement("
            CREATE TRIGGER update_device_last_seen_on_login
            AFTER INSERT ON logins
            FOR EACH ROW
            BEGIN
                UPDATE devices 
                SET last_seen_at = NEW.created_at 
                WHERE id = NEW.device_id;
            END
        ");
        
        // Trigger to update device last_seen_at on activity
        DB::statement("
            CREATE TRIGGER update_device_last_seen_on_activity
            AFTER INSERT ON device_activities
            FOR EACH ROW
            BEGIN
                UPDATE devices 
                SET last_seen_at = NEW.occurred_at 
                WHERE id = NEW.device_id;
            END
        ");
        
        // Trigger to update device last_seen_at on session activity
        DB::statement("
            CREATE TRIGGER update_device_last_seen_on_session_activity
            AFTER UPDATE ON device_sessions
            FOR EACH ROW
            BEGIN
                IF NEW.last_activity_at != OLD.last_activity_at THEN
                    UPDATE devices 
                    SET last_seen_at = NEW.last_activity_at 
                    WHERE id = NEW.device_id;
                END IF;
            END
        ");
        
        // Trigger to automatically create device statistics
        DB::statement("
            CREATE TRIGGER create_device_statistics_on_login
            AFTER INSERT ON logins
            FOR EACH ROW
            BEGIN
                INSERT INTO device_statistics (
                    device_id, 
                    date, 
                    login_count, 
                    first_activity_at, 
                    last_activity_at,
                    created_at,
                    updated_at
                )
                VALUES (
                    NEW.device_id,
                    DATE(NEW.created_at),
                    1,
                    NEW.created_at,
                    NEW.created_at,
                    NOW(),
                    NOW()
                )
                ON DUPLICATE KEY UPDATE
                    login_count = login_count + 1,
                    last_activity_at = NEW.created_at,
                    updated_at = NOW();
            END
        ");
        
        // Trigger to automatically create user statistics
        DB::statement("
            CREATE TRIGGER create_user_statistics_on_login
            AFTER INSERT ON logins
            FOR EACH ROW
            BEGIN
                INSERT INTO user_statistics (
                    user_id, 
                    date, 
                    login_count, 
                    first_login_at, 
                    last_login_at,
                    created_at,
                    updated_at
                )
                VALUES (
                    NEW.user_id,
                    DATE(NEW.created_at),
                    1,
                    NEW.created_at,
                    NEW.created_at,
                    NOW(),
                    NOW()
                )
                ON DUPLICATE KEY UPDATE
                    login_count = login_count + 1,
                    last_login_at = NEW.created_at,
                    updated_at = NOW();
            END
        ");
        
        // Trigger to automatically create system statistics
        DB::statement("
            CREATE TRIGGER create_system_statistics_on_login
            AFTER INSERT ON logins
            FOR EACH ROW
            BEGIN
                INSERT INTO system_statistics (
                    date, 
                    total_logins,
                    created_at,
                    updated_at
                )
                VALUES (
                    DATE(NEW.created_at),
                    1,
                    NOW(),
                    NOW()
                )
                ON DUPLICATE KEY UPDATE
                    total_logins = total_logins + 1,
                    updated_at = NOW();
            END
        ");
        
        // Trigger to automatically create audit log on device changes
        DB::statement("
            CREATE TRIGGER create_audit_log_on_device_update
            AFTER UPDATE ON devices
            FOR EACH ROW
            BEGIN
                IF OLD.is_active != NEW.is_active OR OLD.is_trusted != NEW.is_trusted THEN
                    INSERT INTO audit_logs (
                        event,
                        auditable_type,
                        auditable_id,
                        old_values,
                        new_values,
                        created_at,
                        updated_at
                    )
                    VALUES (
                        'device_updated',
                        'App\\Models\\Device',
                        NEW.id,
                        JSON_OBJECT('is_active', OLD.is_active, 'is_trusted', OLD.is_trusted),
                        JSON_OBJECT('is_active', NEW.is_active, 'is_trusted', NEW.is_trusted),
                        NOW(),
                        NOW()
                    );
                END IF;
            END
        ");
        
        // Trigger to automatically create audit log on device creation
        DB::statement("
            CREATE TRIGGER create_audit_log_on_device_create
            AFTER INSERT ON devices
            FOR EACH ROW
            BEGIN
                INSERT INTO audit_logs (
                    event,
                    auditable_type,
                    auditable_id,
                    old_values,
                    new_values,
                    created_at,
                    updated_at
                )
                VALUES (
                    'device_created',
                    'App\\Models\\Device',
                    NEW.id,
                    NULL,
                    JSON_OBJECT('udid', NEW.udid, 'os', NEW.os, 'model', NEW.model, 'manufacturer', NEW.manufacturer),
                    NOW(),
                    NOW()
                );
            END
        ");
        
        // Trigger to automatically create audit log on device deletion
        DB::statement("
            CREATE TRIGGER create_audit_log_on_device_delete
            AFTER UPDATE ON devices
            FOR EACH ROW
            BEGIN
                IF OLD.deleted_at IS NULL AND NEW.deleted_at IS NOT NULL THEN
                    INSERT INTO audit_logs (
                        event,
                        auditable_type,
                        auditable_id,
                        old_values,
                        new_values,
                        created_at,
                        updated_at
                    )
                    VALUES (
                        'device_deleted',
                        'App\\Models\\Device',
                        NEW.id,
                        JSON_OBJECT('deleted_at', NULL),
                        JSON_OBJECT('deleted_at', NEW.deleted_at),
                        NOW(),
                        NOW()
                    );
                END IF;
            END
        ");
        
        // Trigger to automatically create audit log on login
        DB::statement("
            CREATE TRIGGER create_audit_log_on_login
            AFTER INSERT ON logins
            FOR EACH ROW
            BEGIN
                INSERT INTO audit_logs (
                    event,
                    auditable_type,
                    auditable_id,
                    old_values,
                    new_values,
                    created_at,
                    updated_at
                )
                VALUES (
                    'user_login',
                    'App\\Models\\User',
                    NEW.user_id,
                    NULL,
                    JSON_OBJECT('device_id', NEW.device_id, 'ip_address', NEW.ip_address, 'user_agent', NEW.user_agent),
                    NOW(),
                    NOW()
                );
            END
        ");
        
        // Trigger to automatically create audit log on logout
        DB::statement("
            CREATE TRIGGER create_audit_log_on_logout
            AFTER UPDATE ON logins
            FOR EACH ROW
            BEGIN
                IF OLD.logout_at IS NULL AND NEW.logout_at IS NOT NULL THEN
                    INSERT INTO audit_logs (
                        event,
                        auditable_type,
                        auditable_id,
                        old_values,
                        new_values,
                        created_at,
                        updated_at
                    )
                    VALUES (
                        'user_logout',
                        'App\\Models\\User',
                        NEW.user_id,
                        JSON_OBJECT('logout_at', NULL),
                        JSON_OBJECT('logout_at', NEW.logout_at),
                        NOW(),
                        NOW()
                    );
                END IF;
            END
        ");
        
        // Trigger to automatically create audit log on security events
        DB::statement("
            CREATE TRIGGER create_audit_log_on_security_event
            AFTER INSERT ON security_events
            FOR EACH ROW
            BEGIN
                INSERT INTO audit_logs (
                    event,
                    auditable_type,
                    auditable_id,
                    old_values,
                    new_values,
                    created_at,
                    updated_at
                )
                VALUES (
                    'security_event',
                    NEW.trackable_type,
                    NEW.trackable_id,
                    NULL,
                    JSON_OBJECT('event_type', NEW.event_type, 'risk_score', NEW.risk_score, 'ip_address', NEW.ip_address),
                    NOW(),
                    NOW()
                );
            END
        ");
        
        // Trigger to automatically create audit log on notification
        DB::statement("
            CREATE TRIGGER create_audit_log_on_notification
            AFTER INSERT ON device_notifications
            FOR EACH ROW
            BEGIN
                INSERT INTO audit_logs (
                    event,
                    auditable_type,
                    auditable_id,
                    old_values,
                    new_values,
                    created_at,
                    updated_at
                )
                VALUES (
                    'notification_sent',
                    'App\\Models\\Device',
                    NEW.device_id,
                    NULL,
                    JSON_OBJECT('type', NEW.type, 'channel', NEW.channel, 'sent', NEW.sent),
                    NOW(),
                    NOW()
                );
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
        // Drop triggers
        DB::statement("DROP TRIGGER IF EXISTS update_device_last_seen_on_login");
        DB::statement("DROP TRIGGER IF EXISTS update_device_last_seen_on_activity");
        DB::statement("DROP TRIGGER IF EXISTS update_device_last_seen_on_session_activity");
        DB::statement("DROP TRIGGER IF EXISTS create_device_statistics_on_login");
        DB::statement("DROP TRIGGER IF EXISTS create_user_statistics_on_login");
        DB::statement("DROP TRIGGER IF EXISTS create_system_statistics_on_login");
        DB::statement("DROP TRIGGER IF EXISTS create_audit_log_on_device_update");
        DB::statement("DROP TRIGGER IF EXISTS create_audit_log_on_device_create");
        DB::statement("DROP TRIGGER IF EXISTS create_audit_log_on_device_delete");
        DB::statement("DROP TRIGGER IF EXISTS create_audit_log_on_login");
        DB::statement("DROP TRIGGER IF EXISTS create_audit_log_on_logout");
        DB::statement("DROP TRIGGER IF EXISTS create_audit_log_on_security_event");
        DB::statement("DROP TRIGGER IF EXISTS create_audit_log_on_notification");
    }
};
