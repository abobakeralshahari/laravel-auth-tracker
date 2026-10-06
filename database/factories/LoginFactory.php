<?php

namespace Awsan\AuthTracker\Database\Factories;

use Awsan\AuthTracker\Models\Device;
use Awsan\AuthTracker\Models\Login;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @extends Factory<Login>
 */
class LoginFactory extends Factory
{
    protected $model = Login::class;

    public function definition(): array
    {
        $credential = Str::random(40);

        return [
            'guard' => 'web',
            'driver' => 'session',
            'credential_id' => $credential,
            'session_id' => $credential,
            'ip' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0',
            'device_type' => 'Desktop',
            'platform' => 'Windows',
            'browser' => 'Chrome',
            'login_by' => 'password',
            'login_from' => 'web_pc',
            'last_activity_at' => now(),
            'expires_at' => now()->addDays(30),
        ];
    }

    public function ownedBy(Model $user): static
    {
        return $this->state([
            'authenticatable_type' => $user->getMorphClass(),
            'authenticatable_id' => $user->getKey(),
        ]);
    }

    public function onDevice(Device $device): static
    {
        return $this->state(['device_id' => $device->getKey()]);
    }

    public function sanctum(int|string $tokenId): static
    {
        return $this->state(['driver' => 'sanctum', 'guard' => 'sanctum', 'credential_id' => (string) $tokenId, 'session_id' => null, 'personal_access_token_id' => $tokenId]);
    }

    public function passport(string $tokenId): static
    {
        return $this->state(['driver' => 'passport', 'guard' => 'api', 'credential_id' => $tokenId, 'session_id' => null, 'oauth_access_token_id' => $tokenId]);
    }

    public function revoked(string $reason = 'user'): static
    {
        return $this->state(['revoked_at' => now(), 'revoked_reason' => $reason, 'cleared_by_user' => true, 'logout_at' => now()]);
    }

    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subMinute()]);
    }

    public function from(string $country, ?float $lat = null, ?float $lng = null): static
    {
        return $this->state(['country' => $country, 'latitude' => $lat, 'longitude' => $lng]);
    }
}
