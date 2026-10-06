<?php

namespace OwaisKit\AuthTracker\Tests;

use OwaisKit\AuthTracker\Actions\AssessRisk;
use OwaisKit\AuthTracker\Events\SessionLimitExceeded;
use OwaisKit\AuthTracker\Exceptions\InvalidRefreshTokenException;
use OwaisKit\AuthTracker\Exceptions\RefreshTokenReusedException;
use OwaisKit\AuthTracker\Exceptions\SessionLimitExceededException;
use OwaisKit\AuthTracker\Facades\AuthTracker;
use OwaisKit\AuthTracker\Models\AuthAttempt;
use OwaisKit\AuthTracker\Models\Device;
use OwaisKit\AuthTracker\Models\Login;
use Carbon\CarbonInterval;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\PersonalAccessToken;

class PoliciesTest extends TestCase
{
    public function test_revoke_oldest_keeps_the_newest_sessions(): void
    {
        config()->set('auth_tracker.trackables', [User::class => ['max_sessions' => 2]]);
        $user = $this->createUser();

        $old = Login::factory()->ownedBy($user)->create(['last_activity_at' => now()->subDays(2)]);
        $recent = Login::factory()->ownedBy($user)->create(['last_activity_at' => now()->subDay()]);

        Auth::login($user);

        $this->assertTrue($old->fresh()->isRevoked());
        $this->assertSame('session_limit', $old->fresh()->revoked_reason);
        $this->assertFalse($recent->fresh()->isRevoked());
        $this->assertCount(2, AuthTracker::active($user));
    }

    public function test_reject_revokes_the_new_login_and_throws(): void
    {
        config()->set('auth_tracker.trackables', [User::class => ['max_sessions' => 1, 'on_exceed' => 'reject']]);
        $user = $this->createUser();
        Login::factory()->ownedBy($user)->create();

        try {
            Auth::login($user);
            $this->fail('Expected SessionLimitExceededException');
        } catch (SessionLimitExceededException $e) {
            $this->assertSame(1, $e->limit);
        }

        $this->assertCount(1, AuthTracker::active($user));
        $this->assertFalse(Auth::check());
    }

    public function test_ask_only_dispatches_the_event(): void
    {
        Event::fake([SessionLimitExceeded::class]);
        config()->set('auth_tracker.trackables', [User::class => ['max_sessions' => 1, 'on_exceed' => 'ask']]);
        $user = $this->createUser();
        Login::factory()->ownedBy($user)->create();

        Auth::login($user);

        Event::assertDispatched(SessionLimitExceeded::class, fn ($e) => $e->excess->count() === 1);
        $this->assertCount(2, AuthTracker::active($user));
    }

    public function test_session_limit_scope_per_guard(): void
    {
        config()->set('auth_tracker.trackables', [User::class => ['max_sessions' => 1]]);
        $user = $this->createUser();
        $api = Login::factory()->ownedBy($user)->sanctum(99)->create();

        Auth::login($user);

        $this->assertFalse($api->fresh()->isRevoked());
    }

    public function test_risk_flags_new_device_and_country(): void
    {
        $fake = AuthTracker::fake();
        $user = $this->createUser();
        Login::factory()->ownedBy($user)->onDevice(Device::factory()->create())->from('YE')->create();

        Auth::login($user); // new device (generated), no country → new_device only

        $login = $user->currentLogin();
        $this->assertSame(['new_device'], $login->risk_flags);
        $fake->assertSuspicious($user, AssessRisk::NEW_DEVICE);
    }

    public function test_impossible_travel(): void
    {
        $user = $this->createUser();
        $device = Device::factory()->create();
        Login::factory()->ownedBy($user)->onDevice($device)->from('YE', 15.35, 44.2)->create(['created_at' => now()->subMinutes(30)]);
        $new = Login::factory()->ownedBy($user)->onDevice($device)->from('YE', 40.71, -74.0)->create();

        $flags = app(AssessRisk::class)->execute($user, $new);

        $this->assertContains(AssessRisk::IMPOSSIBLE_TRAVEL, $flags);
        $this->assertNotContains(AssessRisk::NEW_DEVICE, $flags);
    }

    public function test_first_login_is_never_suspicious(): void
    {
        $fake = AuthTracker::fake();
        Auth::login($this->createUser());

        $fake->assertNothingSuspicious();
    }

    public function test_failed_attempts_are_recorded(): void
    {
        $this->createUser(User::class, ['email' => 'a@example.com']);

        Auth::attempt(['email' => 'a@example.com', 'password' => 'wrong']);

        $attempt = AuthAttempt::first();
        $this->assertSame('a@example.com', $attempt->identifier);
        $this->assertSame(AuthAttempt::REASON_INVALID_CREDENTIALS, $attempt->reason);
        $this->assertSame('web', $attempt->guard);
    }

    public function test_trusted_devices(): void
    {
        $user = $this->createUser();
        $other = $this->createUser();

        Auth::login($user);
        $device = AuthTracker::currentDevice();

        $this->assertFalse(AuthTracker::isTrustedDevice($device, $user));

        AuthTracker::trustDevice($device, CarbonInterval::days(30));

        $this->assertTrue(AuthTracker::isTrustedDevice($device, $user));
        $this->assertFalse(AuthTracker::isTrustedDevice($device, $other), 'Trust is per user');
        $this->assertTrue($device->fresh()->trusted_until->isFuture());

        AuthTracker::blockDevice($device);

        $this->assertTrue($device->fresh()->isBlocked());
        $this->assertFalse(AuthTracker::isTrustedDevice($device->fresh(), $user));
        $this->assertCount(0, AuthTracker::active($user));
    }

    public function test_refreshable_sanctum_tokens(): void
    {
        $user = $this->createUser();

        $issued = AuthTracker::issueToken($user, 'phone');

        $this->assertCount(1, $user->logins);
        $this->assertNotNull($issued->refreshToken);

        $this->getJson('/sanctum/check', ['Authorization' => 'Bearer '.$issued->accessToken])
            ->assertOk()->assertJsonPath('id', $issued->login->id);

        $refreshed = AuthTracker::refreshToken($issued->refreshToken);

        $this->assertTrue($refreshed->login->is($issued->login));
        $this->assertSame(1, $refreshed->login->rotations);
        $this->assertCount(1, $user->logins()->withExpired()->get());
        $this->assertSame(1, PersonalAccessToken::count(), 'The previous access token is deleted');

        // Reuse of the rotated refresh token reveals a theft: the login is revoked.
        try {
            AuthTracker::refreshToken($issued->refreshToken);
            $this->fail('Expected RefreshTokenReusedException');
        } catch (RefreshTokenReusedException) {
        }

        $this->assertTrue($refreshed->login->fresh()->isRevoked());
        $this->assertSame('security', $refreshed->login->fresh()->revoked_reason);

        $this->expectException(InvalidRefreshTokenException::class);
        AuthTracker::refreshToken($refreshed->refreshToken);
    }

    public function test_routes(): void
    {
        AuthTracker::routes(prefix: 'account/security', middleware: ['auth:sanctum']);

        $user = $this->createUser();
        $issued = AuthTracker::issueToken($user);
        $headers = ['Authorization' => 'Bearer '.$issued->accessToken];
        $other = Login::factory()->ownedBy($user)->create();

        $this->getJson('/account/security/sessions', $headers)->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/account/security/sessions/current', $headers)->assertOk()->assertJsonPath('data.is_current', true);
        $this->deleteJson("/account/security/sessions/{$other->id}", [], $headers)->assertOk()->assertJson(['revoked' => true]);
        $this->getJson('/account/security/sessions/history', $headers)->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/account/security/devices', $headers)->assertOk()->assertJsonCount(1, 'data');

        $device = AuthTracker::devices($user)->first();
        $this->patchJson("/account/security/devices/{$device->id}", ['name' => 'My phone'], $headers)->assertOk()->assertJsonPath('data.name', 'My phone');
        $this->postJson("/account/security/devices/{$device->id}/trust", ['days' => 7], $headers)->assertOk()->assertJsonPath('data.is_trusted', true);

        $this->postJson('/account/security/token/refresh', ['refresh_token' => $issued->refreshToken])
            ->assertOk()->assertJsonStructure(['access_token', 'refresh_token', 'expires_in']);

        $this->postJson('/account/security/token/refresh', ['refresh_token' => 'nope'])->assertStatus(401);
    }

    public function test_prune(): void
    {
        $user = $this->createUser();
        Login::factory()->ownedBy($user)->revoked()->create(['revoked_at' => now()->subDays(100)]);
        Login::factory()->ownedBy($user)->create();
        AuthAttempt::create(['reason' => 'x', 'attempted_at' => now()->subDays(100)]);
        Device::factory()->create(['last_seen_at' => now()->subDays(200)]);

        $this->artisan('tracker:prune')->assertSuccessful();

        $this->assertSame(1, Login::withExpired()->count());
        $this->assertSame(0, AuthAttempt::count());
        $this->assertSame(0, Device::count());
    }

    public function test_doctor(): void
    {
        config()->set('session.driver', 'database');

        $this->artisan('tracker:doctor')->assertSuccessful();
    }
}
