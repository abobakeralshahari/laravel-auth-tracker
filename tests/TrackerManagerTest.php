<?php

namespace Alshahari\AuthTracker\Tests;

use Alshahari\AuthTracker\Contracts\TrackerDriver;
use Alshahari\AuthTracker\Drivers\SessionDriver;
use Alshahari\AuthTracker\Events\SessionRevoked;
use Alshahari\AuthTracker\Events\SessionStarted;
use Alshahari\AuthTracker\Facades\AuthTracker;
use Alshahari\AuthTracker\Models\Login;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

class TrackerManagerTest extends TestCase
{
    public function test_drivers_are_inferred_from_the_auth_guards(): void
    {
        $this->assertSame('session', AuthTracker::driverNameFor('web'));
        $this->assertSame('passport', AuthTracker::driverNameFor('api'));
        $this->assertSame('sanctum', AuthTracker::driverNameFor('sanctum'));
        $this->assertSame('api', AuthTracker::guardForDriver('passport'));
    }

    public function test_drivers_can_be_configured_per_guard_and_extended(): void
    {
        config()->set('auth_tracker.guards.admin.driver', 'custom');

        AuthTracker::extend('custom', fn () => new class implements TrackerDriver
        {
            public function name(): string
            {
                return 'custom';
            }

            public function legacyColumn(): ?string
            {
                return null;
            }

            public function currentCredentialId(Authenticatable $user): ?string
            {
                return null;
            }

            public function revoke(Login $login): void
            {
            }
        });

        $this->assertSame('custom', AuthTracker::driverFor('admin')->name());
        $this->assertContains('custom', AuthTracker::driverNames());
    }

    public function test_session_login_records_guard_and_driver_and_fires_events(): void
    {
        Event::fake([SessionStarted::class, SessionRevoked::class]);

        $user = $this->createUser();
        Auth::login($user);

        $login = $user->currentLogin();

        $this->assertSame('web', $login->guard);
        $this->assertSame('session', $login->driver);
        $this->assertSame(session()->getId(), $login->credential_id);
        $this->assertNotNull($login->last_activity_at);
        Event::assertDispatched(SessionStarted::class, fn ($e) => $e->login->is($login));

        AuthTracker::revoke($login, 'admin');

        $this->assertSame('admin', $login->fresh()->revoked_reason);
        $this->assertNotNull($login->fresh()->revoked_at);
        Event::assertDispatched(SessionRevoked::class, fn ($e) => $e->reason === 'admin');
    }

    public function test_activity_is_touched_at_most_once_per_interval(): void
    {
        config()->set('auth_tracker.activity.touch_interval', 60);

        $user = $this->createUser();
        Auth::login($user);
        $login = $user->currentLogin();

        $login->newQueryWithoutScopes()->whereKey($login->id)->update(['last_activity_at' => now()->subHour()]);

        // Second Authenticated event of the same request window: throttled.
        Auth::setUser($user);
        $this->assertTrue($login->fresh()->last_activity_at->lt(now()->subMinutes(30)));

        Cache::flush();
        Auth::setUser($user);
        $this->assertTrue($login->fresh()->last_activity_at->gt(now()->subMinute()));
    }

    public function test_facade_forwards_session_operations(): void
    {
        $user = $this->createUser();
        $user->logins()->save(new Login(['driver' => 'session', 'credential_id' => 'a', 'session_id' => 'a']));
        $user->logins()->save(new Login(['driver' => 'session', 'credential_id' => 'b', 'session_id' => 'b']));

        $this->assertCount(2, AuthTracker::active($user));
        $this->assertSame(2, AuthTracker::revokeAll($user));
        $this->assertCount(0, AuthTracker::active($user));
        $this->assertCount(2, AuthTracker::history($user));
        $this->assertInstanceOf(SessionDriver::class, AuthTracker::driver());
    }
}
