<?php

namespace Alshahari\AuthTracker\Database\Factories;

use Alshahari\AuthTracker\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
{
    protected $model = Device::class;

    public function definition(): array
    {
        return [
            'udid' => Str::random(64),
            'type' => 'phone',
            'os' => 'android',
            'os_version' => '14',
            'manufacturer' => 'samsung',
            'model' => 'SM-S911B',
            'app_type' => 'native_android',
            'app_version' => '2.0.0',
            'user_agent' => 'TestApp/2.0 (android 14)',
            'last_seen_at' => now(),
        ];
    }

    public function desktop(): static
    {
        return $this->state([
            'type' => 'desktop', 'os' => 'windows', 'os_version' => '11',
            'manufacturer' => null, 'model' => 'Chrome', 'browser' => 'Chrome', 'app_type' => 'web_pc',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0',
        ]);
    }

    public function trusted(): static
    {
        return $this->state(['trusted_at' => now()]);
    }

    public function blocked(): static
    {
        return $this->state(['blocked_at' => now()]);
    }
}
