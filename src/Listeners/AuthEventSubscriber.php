<?php

namespace Alshahari\AuthTracker\Listeners;

use Alshahari\AuthTracker\AuthTracker;
use Alshahari\AuthTracker\Events\Login as LoginTracked;
use Alshahari\AuthTracker\Factories\LoginFactory;
use Alshahari\AuthTracker\Models\Login;
use Alshahari\AuthTracker\RequestContext;
use Carbon\Carbon;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\Login as LoginEvent;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Recaller;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Session\Session;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Tracks session based logins.
 */
class AuthEventSubscriber
{
    /**
     * Session key caching the session id known by the login record.
     */
    const SESSION_ID_KEY = 'auth_tracker.session_id';

    public function handleSuccessfulLogin(LoginEvent $event): void
    {
        if (! AuthTracker::isTracked($event->user) || ! $this->session()) {
            return;
        }

        $session = $this->session();

        if (Auth::guard($event->guard)->viaRemember()) {
            $this->handleRememberedLogin($event, $session);

            return;
        }

        $context = new RequestContext;

        $login = LoginFactory::build($event, $context);

        $login->expiresAt($event->remember
            ? Carbon::now()->addDays((int) config('auth_tracker.remember_lifetime', 365))
            : Carbon::now()->addMinutes((int) config('session.lifetime', 120)));

        $event->user->logins()->save($login);

        $this->bindLoginToSession($login, $session);

        // Each session gets its own remember token so that a single
        // session can be revoked without affecting the others.
        $this->updateRememberToken($event->user, Str::random(60));

        event(new LoginTracked($event->user, $context));
    }

    /**
     * A user came back with a remember cookie: bind the new session to
     * the login that owns the remember token.
     */
    protected function handleRememberedLogin(LoginEvent $event, Session $session): void
    {
        if (is_null($recaller = $this->recaller($event->guard))) {
            return;
        }

        $login = $event->user->logins()
            ->where('remember_token', $recaller->token())
            ->first();

        if ($login) {
            $login->forceFill(['session_id' => $session->getId()])->save();
            $this->bindLoginToSession($login, $session);
        }
    }

    /**
     * Keep the stored session id in sync when the application regenerates
     * the session id after the login event (Breeze, Fortify, Filament...).
     */
    public function handleAuthenticated(Authenticated $event): void
    {
        $session = $this->session();

        if (! $session || ! $session->has(Login::SESSION_KEY)) {
            return;
        }

        if ($session->get(self::SESSION_ID_KEY) === $session->getId()) {
            return;
        }

        AuthTracker::loginModel()::withoutGlobalScopes()
            ->whereKey($session->get(Login::SESSION_KEY))
            ->update(['session_id' => $session->getId()]);

        $session->put(self::SESSION_ID_KEY, $session->getId());
    }

    public function handleSuccessfulLogout(Logout $event): void
    {
        if (! $event->user || ! AuthTracker::isTracked($event->user)) {
            return;
        }

        $session = $this->session();

        $query = $event->user->logins();

        if ($session && $session->has(Login::SESSION_KEY)) {
            $query->whereKey($session->get(Login::SESSION_KEY));
        } elseif ($session) {
            $query->where('session_id', $session->getId());
        } else {
            return;
        }

        $query->update(['cleared_by_user' => true, 'logout_at' => now(), 'remember_token' => null]);

        $session->forget([Login::SESSION_KEY, self::SESSION_ID_KEY]);
    }

    protected function bindLoginToSession(Login $login, Session $session): void
    {
        $session->put(Login::SESSION_KEY, $login->getKey());
        $session->put(self::SESSION_ID_KEY, $session->getId());
    }

    /**
     * Get the decrypted recaller cookie for the request.
     */
    protected function recaller(?string $guard): ?Recaller
    {
        $request = app()->bound('request') ? request() : null;

        if (! $request) {
            return null;
        }

        $name = Auth::guard($guard)->getRecallerName();

        return ($recaller = $request->cookies->get($name)) ? new Recaller($recaller) : null;
    }

    /**
     * Update the "remember me" token for the given user in storage.
     */
    protected function updateRememberToken(Authenticatable $user, string $token): void
    {
        $user->setRememberToken($token);

        $timestamps = $user->timestamps;
        $user->timestamps = false;
        $user->save();
        $user->timestamps = $timestamps;
    }

    protected function session(): ?Session
    {
        $request = app()->bound('request') ? request() : null;

        return $request && $request->hasSession() ? $request->session() : null;
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            LoginEvent::class => 'handleSuccessfulLogin',
            Authenticated::class => 'handleAuthenticated',
            Logout::class => 'handleSuccessfulLogout',
        ];
    }
}
