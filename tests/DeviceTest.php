<?php

namespace Awsan\AuthTracker\Tests;

use Awsan\AuthTracker\Facades\AuthTracker;
use Awsan\AuthTracker\Models\Device;
use Illuminate\Support\Facades\Auth;

class DeviceTest extends TestCase
{
    protected array $headers = [
        'x-device-udid' => 'ABC-123',
        'x-device-os' => 'android',
        'x-device-os-version' => '14',
        'x-device-manufacturer' => 'samsung',
        'x-device-model' => 'SM-S911B',
        'x-device-fcm-token' => 'fcm-token-1',
        'x-device-app-version' => '2.1.0',
        'x-device-app-type' => 'native_android',
    ];

    public function test_device_is_created_from_the_headers(): void
    {
        $this->getJson('/device', $this->headers)->assertOk()->assertJsonPath('udid', 'ABC-123');

        $device = Device::where('udid', 'ABC-123')->first();

        $this->assertSame('samsung', $device->manufacturer);
        $this->assertSame('fcm-token-1', $device->fcm_token);
        $this->assertSame('native_android', $device->app_type);
        $this->assertNotNull($device->last_seen_at);
    }

    public function test_missing_headers_do_not_erase_existing_data(): void
    {
        $this->getJson('/device', $this->headers);

        $this->getJson('/device', ['x-device-udid' => 'ABC-123'])->assertOk();

        $device = Device::where('udid', 'ABC-123')->first();

        $this->assertSame('fcm-token-1', $device->fcm_token);
        $this->assertSame('2.1.0', $device->app_version);
        $this->assertSame(1, Device::count());
    }

    public function test_a_device_is_generated_for_browsers(): void
    {
        $this->get('/device', ['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0'])->assertOk();

        $device = Device::first();

        $this->assertSame(64, strlen($device->udid));
        $this->assertSame('desktop', $device->type);
        $this->assertSame('web_pc', $device->app_type);
    }

    public function test_login_is_linked_to_the_device_and_the_device_to_the_user(): void
    {
        $user = $this->createUser();

        Auth::login($user);

        $login = $user->currentLogin();

        $this->assertNotNull($login->device);
        $this->assertCount(1, $login->device->logins);
    }

    public function test_tenant_resolver(): void
    {
        AuthTracker::resolveTenantUsing(fn () => 'ye');

        $this->getJson('/device', $this->headers);

        $this->assertSame('ye', Device::first()->tenant_id);
    }
}
