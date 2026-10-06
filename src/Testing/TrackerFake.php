<?php

namespace Awsan\AuthTracker\Testing;

use Awsan\AuthTracker\Events\SessionRevoked;
use Awsan\AuthTracker\Events\SessionStarted;
use Awsan\AuthTracker\Events\SuspiciousLogin;
use Awsan\AuthTracker\Facades\AuthTracker;
use Awsan\AuthTracker\Models\Login;
use Awsan\AuthTracker\TrackerManager;
use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Assert as PHPUnit;

/**
 * AuthTracker::fake() keeps the tracking fully functional (logins are
 * still recorded) and records the package events for assertions.
 *
 *   AuthTracker::fake();
 *   ...
 *   AuthTracker::assertSessionStarted($user);
 *   AuthTracker::assertRevoked($login, 'admin');
 */
class TrackerFake extends TrackerManager
{
    /** @var Collection<int, SessionStarted> */
    public Collection $started;

    /** @var Collection<int, SessionRevoked> */
    public Collection $revoked;

    /** @var Collection<int, SuspiciousLogin> */
    public Collection $suspicious;

    public static function install(Application $app): static
    {
        $fake = new static($app);

        $app->instance(TrackerManager::class, $fake);
        AuthTracker::clearResolvedInstance(TrackerManager::class);

        return $fake;
    }

    public function __construct($container)
    {
        parent::__construct($container);

        $this->started = new Collection;
        $this->revoked = new Collection;
        $this->suspicious = new Collection;

        Event::listen(SessionStarted::class, fn ($event) => $this->started->push($event));
        Event::listen(SessionRevoked::class, fn ($event) => $this->revoked->push($event));
        Event::listen(SuspiciousLogin::class, fn ($event) => $this->suspicious->push($event));
    }

    public function assertSessionStarted(?Authenticatable $user = null, ?Closure $callback = null): void
    {
        $matches = $this->started->filter(fn (SessionStarted $event) => (! $user || $event->user->is($user)) && (! $callback || $callback($event->login)));

        PHPUnit::assertGreaterThan(0, $matches->count(), 'No tracked session was started.');
    }

    public function assertNoSessionStarted(?Authenticatable $user = null): void
    {
        $matches = $this->started->filter(fn (SessionStarted $event) => ! $user || $event->user->is($user));

        PHPUnit::assertSame(0, $matches->count(), 'A tracked session was started unexpectedly.');
    }

    public function assertRevoked(Login $login, ?string $reason = null): void
    {
        $matches = $this->revoked->filter(fn (SessionRevoked $event) => $event->login->is($login) && ($reason === null || $event->reason === $reason));

        PHPUnit::assertGreaterThan(0, $matches->count(), 'The login was not revoked'.($reason ? " with reason [{$reason}]" : '').'.');
    }

    public function assertNotRevoked(Login $login): void
    {
        PHPUnit::assertFalse($this->revoked->contains(fn (SessionRevoked $event) => $event->login->is($login)), 'The login was revoked.');
    }

    public function assertSuspicious(?Authenticatable $user = null, ?string $flag = null): void
    {
        $matches = $this->suspicious->filter(fn (SuspiciousLogin $event) => (! $user || $event->user->is($user)) && ($flag === null || $event->has($flag)));

        PHPUnit::assertGreaterThan(0, $matches->count(), 'No suspicious login was detected.');
    }

    public function assertNothingSuspicious(): void
    {
        PHPUnit::assertCount(0, $this->suspicious, 'A suspicious login was detected.');
    }
}
