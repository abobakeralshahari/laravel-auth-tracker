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
        // Create custom functions for common operations
        
        // Function to calculate device risk score
        DB::statement("
            CREATE FUNCTION CalculateDeviceRiskScore(device_id INT)
            RETURNS INT
            READS SQL DATA
            DETERMINISTIC
            BEGIN
                DECLARE risk_score INT DEFAULT 0;
                DECLARE device_age_days INT;
                DECLARE login_count INT;
                DECLARE suspicious_events INT;
                DECLARE trusted_status BOOLEAN;
                
                -- Get device information
                SELECT 
                    DATEDIFF(NOW(), created_at),
                    COUNT(l.id),
                    COUNT(se.id),
                    is_trusted
                INTO device_age_days, login_count, suspicious_events, trusted_status
                FROM devices d
                LEFT JOIN logins l ON d.id = l.device_id
                LEFT JOIN security_events se ON d.id = se.trackable_id AND se.trackable_type = 'App\\Models\\Device'
                WHERE d.id = device_id
                GROUP BY d.id, d.created_at, d.is_trusted;
                
                -- Calculate risk score based on various factors
                -- New device (less than 7 days) adds 20 points
                IF device_age_days < 7 THEN
                    SET risk_score = risk_score + 20;
                END IF;
                
                -- Few logins (less than 5) adds 15 points
                IF login_count < 5 THEN
                    SET risk_score = risk_score + 15;
                END IF;
                
                -- Suspicious events add 25 points each
                SET risk_score = risk_score + (suspicious_events * 25);
                
                -- Trusted device reduces risk by 30 points
                IF trusted_status = 1 THEN
                    SET risk_score = GREATEST(0, risk_score - 30);
                END IF;
                
                -- Cap risk score at 100
                SET risk_score = LEAST(100, risk_score);
                
                RETURN risk_score;
            END
        ");
        
        // Function to check if device is suspicious
        DB::statement("
            CREATE FUNCTION IsDeviceSuspicious(device_id INT)
            RETURNS BOOLEAN
            READS SQL DATA
            DETERMINISTIC
            BEGIN
                DECLARE risk_score INT;
                DECLARE suspicious_count INT;
                
                -- Get risk score
                SET risk_score = CalculateDeviceRiskScore(device_id);
                
                -- Count recent suspicious events
                SELECT COUNT(*) INTO suspicious_count
                FROM security_events
                WHERE trackable_id = device_id 
                AND trackable_type = 'App\\Models\\Device'
                AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR);
                
                -- Device is suspicious if risk score > 50 OR has recent suspicious events
                RETURN (risk_score > 50 OR suspicious_count > 0);
            END
        ");
        
        // Function to get device display name
        DB::statement("
            CREATE FUNCTION GetDeviceDisplayName(device_id INT)
            RETURNS VARCHAR(255)
            READS SQL DATA
            DETERMINISTIC
            BEGIN
                DECLARE display_name VARCHAR(255);
                
                SELECT CONCAT(
                    COALESCE(manufacturer, 'Unknown'), ' ',
                    COALESCE(model, 'Unknown'), ' (',
                    COALESCE(os, 'Unknown'), ')'
                ) INTO display_name
                FROM devices
                WHERE id = device_id;
                
                RETURN COALESCE(display_name, 'Unknown Device');
            END
        ");
        
        // Function to check if token is expired
        DB::statement("
            CREATE FUNCTION IsTokenExpired(token_value VARCHAR(500))
            RETURNS BOOLEAN
            READS SQL DATA
            DETERMINISTIC
            BEGIN
                DECLARE expires_at TIMESTAMP;
                
                SELECT dt.expires_at INTO expires_at
                FROM device_tokens dt
                WHERE dt.token = token_value
                AND dt.is_active = 1
                LIMIT 1;
                
                RETURN (expires_at IS NOT NULL AND expires_at < NOW());
            END
        ");
        
        // Function to get user's active device count
        DB::statement("
            CREATE FUNCTION GetUserActiveDeviceCount(user_id INT)
            RETURNS INT
            READS SQL DATA
            DETERMINISTIC
            BEGIN
                DECLARE device_count INT DEFAULT 0;
                
                SELECT COUNT(*) INTO device_count
                FROM devices
                WHERE user_id = user_id
                AND is_active = 1
                AND deleted_at IS NULL;
                
                RETURN device_count;
            END
        ");
        
        // Function to get user's trusted device count
        DB::statement("
            CREATE FUNCTION GetUserTrustedDeviceCount(user_id INT)
            RETURNS INT
            READS SQL DATA
            DETERMINISTIC
            BEGIN
                DECLARE device_count INT DEFAULT 0;
                
                SELECT COUNT(*) INTO device_count
                FROM devices
                WHERE user_id = user_id
                AND is_trusted = 1
                AND is_active = 1
                AND deleted_at IS NULL;
                
                RETURN device_count;
            END
        ");
        
        // Function to check if user has suspicious activity
        DB::statement("
            CREATE FUNCTION HasUserSuspiciousActivity(user_id INT)
            RETURNS BOOLEAN
            READS SQL DATA
            DETERMINISTIC
            BEGIN
                DECLARE suspicious_count INT DEFAULT 0;
                
                SELECT COUNT(*) INTO suspicious_count
                FROM security_events
                WHERE trackable_id = user_id
                AND trackable_type = 'App\\Models\\User'
                AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY);
                
                RETURN (suspicious_count > 0);
            END
        ");
        
        // Function to get device's last activity
        DB::statement("
            CREATE FUNCTION GetDeviceLastActivity(device_id INT)
            RETURNS TIMESTAMP
            READS SQL DATA
            DETERMINISTIC
            BEGIN
                DECLARE last_activity TIMESTAMP;
                
                SELECT last_seen_at INTO last_activity
                FROM devices
                WHERE id = device_id;
                
                RETURN last_activity;
            END
        ");
        
        // Function to check if device is online
        DB::statement("
            CREATE FUNCTION IsDeviceOnline(device_id INT)
            RETURNS BOOLEAN
            READS SQL DATA
            DETERMINISTIC
            BEGIN
                DECLARE last_activity TIMESTAMP;
                DECLARE is_online BOOLEAN DEFAULT FALSE;
                
                SELECT last_seen_at INTO last_activity
                FROM devices
                WHERE id = device_id;
                
                -- Device is considered online if last activity was within 5 minutes
                IF last_activity IS NOT NULL AND last_activity >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN
                    SET is_online = TRUE;
                END IF;
                
                RETURN is_online;
            END
        ");
        
        // Function to get device's login count for period
        DB::statement("
            CREATE FUNCTION GetDeviceLoginCount(device_id INT, days_back INT)
            RETURNS INT
            READS SQL DATA
            DETERMINISTIC
            BEGIN
                DECLARE login_count INT DEFAULT 0;
                
                SELECT COUNT(*) INTO login_count
                FROM logins
                WHERE device_id = device_id
                AND created_at >= DATE_SUB(NOW(), INTERVAL days_back DAY);
                
                RETURN login_count;
            END
        ");
        
        // Function to get user's login count for period
        DB::statement("
            CREATE FUNCTION GetUserLoginCount(user_id INT, days_back INT)
            RETURNS INT
            READS SQL DATA
            DETERMINISTIC
            BEGIN
                DECLARE login_count INT DEFAULT 0;
                
                SELECT COUNT(*) INTO login_count
                FROM logins
                WHERE user_id = user_id
                AND created_at >= DATE_SUB(NOW(), INTERVAL days_back DAY);
                
                RETURN login_count;
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
        // Drop functions
        DB::statement("DROP FUNCTION IF EXISTS CalculateDeviceRiskScore");
        DB::statement("DROP FUNCTION IF EXISTS IsDeviceSuspicious");
        DB::statement("DROP FUNCTION IF EXISTS GetDeviceDisplayName");
        DB::statement("DROP FUNCTION IF EXISTS IsTokenExpired");
        DB::statement("DROP FUNCTION IF EXISTS GetUserActiveDeviceCount");
        DB::statement("DROP FUNCTION IF EXISTS GetUserTrustedDeviceCount");
        DB::statement("DROP FUNCTION IF EXISTS HasUserSuspiciousActivity");
        DB::statement("DROP FUNCTION IF EXISTS GetDeviceLastActivity");
        DB::statement("DROP FUNCTION IF EXISTS IsDeviceOnline");
        DB::statement("DROP FUNCTION IF EXISTS GetDeviceLoginCount");
        DB::statement("DROP FUNCTION IF EXISTS GetUserLoginCount");
    }
};
