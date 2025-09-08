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
        // Seed countries
        DB::table('countries')->insert([
            ['code' => 'US', 'name' => 'United States', 'currency' => 'USD', 'timezone' => 'America/New_York', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'GB', 'name' => 'United Kingdom', 'currency' => 'GBP', 'timezone' => 'Europe/London', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'DE', 'name' => 'Germany', 'currency' => 'EUR', 'timezone' => 'Europe/Berlin', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'FR', 'name' => 'France', 'currency' => 'EUR', 'timezone' => 'Europe/Paris', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'CA', 'name' => 'Canada', 'currency' => 'CAD', 'timezone' => 'America/Toronto', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'AU', 'name' => 'Australia', 'currency' => 'AUD', 'timezone' => 'Australia/Sydney', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'JP', 'name' => 'Japan', 'currency' => 'JPY', 'timezone' => 'Asia/Tokyo', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'CN', 'name' => 'China', 'currency' => 'CNY', 'timezone' => 'Asia/Shanghai', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'IN', 'name' => 'India', 'currency' => 'INR', 'timezone' => 'Asia/Kolkata', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'BR', 'name' => 'Brazil', 'currency' => 'BRL', 'timezone' => 'America/Sao_Paulo', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Seed timezones
        DB::table('timezones')->insert([
            ['name' => 'UTC', 'offset' => '+00:00', 'country_code' => null, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'America/New_York', 'offset' => '-05:00', 'country_code' => 'US', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'America/Los_Angeles', 'offset' => '-08:00', 'country_code' => 'US', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Europe/London', 'offset' => '+00:00', 'country_code' => 'GB', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Europe/Berlin', 'offset' => '+01:00', 'country_code' => 'DE', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Europe/Paris', 'offset' => '+01:00', 'country_code' => 'FR', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Asia/Tokyo', 'offset' => '+09:00', 'country_code' => 'JP', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Asia/Shanghai', 'offset' => '+08:00', 'country_code' => 'CN', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Asia/Kolkata', 'offset' => '+05:30', 'country_code' => 'IN', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Australia/Sydney', 'offset' => '+10:00', 'country_code' => 'AU', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Seed languages
        DB::table('languages')->insert([
            ['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'es', 'name' => 'Spanish', 'native_name' => 'Español', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'fr', 'name' => 'French', 'native_name' => 'Français', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'de', 'name' => 'German', 'native_name' => 'Deutsch', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'it', 'name' => 'Italian', 'native_name' => 'Italiano', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'pt', 'name' => 'Portuguese', 'native_name' => 'Português', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'ru', 'name' => 'Russian', 'native_name' => 'Русский', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'ja', 'name' => 'Japanese', 'native_name' => '日本語', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'ko', 'name' => 'Korean', 'native_name' => '한국어', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'zh', 'name' => 'Chinese', 'native_name' => '中文', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Seed operating systems
        DB::table('operating_systems')->insert([
            ['name' => 'Windows', 'version' => '11', 'family' => 'Windows', 'architecture' => 'x64', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Windows', 'version' => '10', 'family' => 'Windows', 'architecture' => 'x64', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'macOS', 'version' => '14', 'family' => 'macOS', 'architecture' => 'arm64', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'macOS', 'version' => '13', 'family' => 'macOS', 'architecture' => 'x64', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Ubuntu', 'version' => '22.04', 'family' => 'Linux', 'architecture' => 'x64', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'CentOS', 'version' => '8', 'family' => 'Linux', 'architecture' => 'x64', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'iOS', 'version' => '17', 'family' => 'iOS', 'architecture' => 'arm64', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'iOS', 'version' => '16', 'family' => 'iOS', 'architecture' => 'arm64', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Android', 'version' => '14', 'family' => 'Android', 'architecture' => 'arm64', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Android', 'version' => '13', 'family' => 'Android', 'architecture' => 'arm64', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Seed browsers
        DB::table('browsers')->insert([
            ['name' => 'Chrome', 'version' => '120', 'engine' => 'Blink', 'is_mobile' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Chrome', 'version' => '119', 'engine' => 'Blink', 'is_mobile' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Firefox', 'version' => '121', 'engine' => 'Gecko', 'is_mobile' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Firefox', 'version' => '120', 'engine' => 'Gecko', 'is_mobile' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Safari', 'version' => '17', 'engine' => 'WebKit', 'is_mobile' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Safari', 'version' => '16', 'engine' => 'WebKit', 'is_mobile' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Edge', 'version' => '120', 'engine' => 'Blink', 'is_mobile' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Opera', 'version' => '106', 'engine' => 'Blink', 'is_mobile' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Chrome Mobile', 'version' => '120', 'engine' => 'Blink', 'is_mobile' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Safari Mobile', 'version' => '17', 'engine' => 'WebKit', 'is_mobile' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Seed device types
        DB::table('device_types')->insert([
            ['name' => 'iPhone', 'os' => 'iOS', 'category' => 'mobile', 'attributes' => json_encode(['manufacturer' => 'Apple', 'is_mobile' => true]), 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'iPad', 'os' => 'iOS', 'category' => 'tablet', 'attributes' => json_encode(['manufacturer' => 'Apple', 'is_mobile' => true]), 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Android Phone', 'os' => 'Android', 'category' => 'mobile', 'attributes' => json_encode(['manufacturer' => 'Various', 'is_mobile' => true]), 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Android Tablet', 'os' => 'Android', 'category' => 'tablet', 'attributes' => json_encode(['manufacturer' => 'Various', 'is_mobile' => true]), 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Windows Desktop', 'os' => 'Windows', 'category' => 'desktop', 'attributes' => json_encode(['manufacturer' => 'Various', 'is_mobile' => false]), 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'macOS Desktop', 'os' => 'macOS', 'category' => 'desktop', 'attributes' => json_encode(['manufacturer' => 'Apple', 'is_mobile' => false]), 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Linux Desktop', 'os' => 'Linux', 'category' => 'desktop', 'attributes' => json_encode(['manufacturer' => 'Various', 'is_mobile' => false]), 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Seed notification channels
        DB::table('notification_channels')->insert([
            ['name' => 'email', 'driver' => 'email', 'config' => json_encode(['smtp' => true]), 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'push', 'driver' => 'push', 'config' => json_encode(['fcm' => true]), 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'database', 'driver' => 'database', 'config' => json_encode(['table' => 'notifications']), 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'webhook', 'driver' => 'webhook', 'config' => json_encode(['url' => 'https://api.example.com/webhook']), 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Seed notification templates
        DB::table('notification_templates')->insert([
            ['type' => 'new_device', 'channel' => 'email', 'subject' => 'New Device Login Detected', 'body' => 'A new device has logged into your account from {{device_name}} ({{ip_address}}).', 'variables' => json_encode(['device_name', 'ip_address', 'location', 'time']), 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'new_device', 'channel' => 'push', 'subject' => null, 'body' => 'New device login: {{device_name}}', 'variables' => json_encode(['device_name', 'ip_address']), 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'suspicious_login', 'channel' => 'email', 'subject' => 'Suspicious Login Activity', 'body' => 'Suspicious login activity detected from {{device_name}} ({{ip_address}}). Please verify this was you.', 'variables' => json_encode(['device_name', 'ip_address', 'location', 'time', 'risk_score']), 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'suspicious_login', 'channel' => 'push', 'subject' => null, 'body' => 'Suspicious login detected: {{device_name}}', 'variables' => json_encode(['device_name', 'ip_address']), 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Seed feature flags
        DB::table('feature_flags')->insert([
            ['name' => 'auto_device_tracking', 'is_enabled' => true, 'description' => 'Automatically track device information on login', 'conditions' => null, 'enabled_at' => now(), 'disabled_at' => null, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'suspicious_login_detection', 'is_enabled' => true, 'description' => 'Detect and alert on suspicious login activities', 'conditions' => null, 'enabled_at' => now(), 'disabled_at' => null, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'device_trust_system', 'is_enabled' => true, 'description' => 'Allow users to trust devices for easier future logins', 'conditions' => null, 'enabled_at' => now(), 'disabled_at' => null, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'push_notifications', 'is_enabled' => true, 'description' => 'Send push notifications for security events', 'conditions' => null, 'enabled_at' => now(), 'disabled_at' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Note: This migration only seeds data, so we don't need to reverse anything
        // The tables will be dropped by their respective migration files
    }
};
