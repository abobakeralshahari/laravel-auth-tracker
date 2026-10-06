<?php

namespace Awsan\AuthTracker\Listeners;

use Awsan\AuthTracker\Actions\RecordAttempt;
use Awsan\AuthTracker\Actions\RecordLogin;
use Awsan\AuthTracker\Actions\RevokeLogin;
use Awsan\AuthTracker\Actions\TouchActivity;
use Awsan\AuthTracker\Models\AuthAttempt;
use Awsan\AuthTracker\Models\Login;
use Awsan\AuthTracker\RequestContext;
use Awsan\AuthTracker\Support\Credential;
use Awsan\AuthTracker\TrackerManager;
use Carbon\Carbon;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login as LoginEvent;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Recaller;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Session\Session;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Tracks session based logins (web guards, Filament panels...).
 */
class AuthEventSubscriber
{
    /**
     * Session key caching the session id known by the login record.
     */
    const SESSION_ID_KEY = 'auth_tracker.session_id';

    public function __construct(
        protected TrackerManager $tracker,
        protected RecordLogin $recorder,
        protected RevokeLogin $revoker,
        protected TouchActivity $toucher,
        protected RecordAttempt $attempts,
    ) {
    }

    public function handleFailed(Failed $event): void
    {
        $this->attempts->execute($event->credentials, AuthAttempt::REASON_INVALID_CREDENTIALS, $event->guard);
    }

    public function handleLockout(Lockout $event): void
    {
        $this->attempts->execute($event->request->only(config('auth_tracker.attempts.identifier_keys', ['email'])), AuthAttempt::REASON_LOCKOUT, null, $event->request);
    }

    public function handleSuccessfulLogin(LoginEvent $event): void
    {
        $session = $this->session();

        if (! $session || ! $this->tracker->isTracked($event->user)) {
            return;
        }

        if ($this->tracker->driverNameFor($event->guard) !== 'session') {
            return; // The guard is tracked by another driver (custom).
        }

        if (Auth::guard($event->guard)->viaRemember()) {
            $this->handleRememberedLogin($event, $session);

            return;
        }

        $expiresAt = $event->remember
            ? Carbon::now()->addDays((int) config('auth_tracker.remember_lifetime', 365))
            : Carbon::now()->addMinutes((int) config('session.lifetime', 120));

        $credential = Credential::session(
            $session->getId(),
            $expiresAt,
            $event->remember ? $event->user->getRememberToken() : null,
        );

        $login = $this->recorder->execute($event->user, $credential, new RequestContext, $event->guard);

        $this->bindLoginToSession($login, $session);

        // Each session gets its own remember token so that a single
        // session can be revoked without affecting the others.
        $this->updateRememberToken($event->user, Str::random(60));
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
            ->active()
            ->where('remember_token', $recaller->token())
            ->first();

        if ($login) {
            $login->forceFill([
                'session_id' => $session->getId(),
                'credential_id' => $session->getId(),
                'last_activity_at' => now(),
            ])->save();

            $this->bindLoginToSession($login, $session);
        }
    }

    /**
     * On every authenticated request: keep the stored session id in sync
     * when the application regenerated it after login (Breeze, Fortify,
     * Filament...) and record the activity.
     */
    public function handleAuthenticated(Authenticated $event): void
    {
        if (! $this->tracker->isTracked($event->user)) {
            return;
        }

        $session = $this->session();

        if ($session && $session->has(Login::SESSION_KEY) && $session->get(self::SESSION_ID_KEY) !== $session->getId()) {
            $this->tracker->loginModel()::withoutGlobalScopes()
                ->whereKey($session->get(Login::SESSION_KEY))
                ->update(['session_id' => $session->getId(), 'credential_id' => $session->getId()]);

            $session->put(self::SESSION_ID_KEY, $session->getId());
        }

        $this->toucher->execute($event->user);
    }

    public function handleSuccessfulLogout(Logout $event): void
    {
        $session = $this->session();

        if (! $session || ! $event->user || ! $this->tracker->isTracked($event->user)) {
            return;
        }

        $login = $session->has(Login::SESSION_KEY)
            ? $event->user->logins()->active()->find($session->get(Login::SESSION_KEY))
            : $event->user->logins()->active()->where('session_id', $session->getId())->first();

        if ($login) {
            // Laravel is already destroying the session: only flag the login.
            $login->markAsRevoked(RevokeLogin::REASON_USER);
            event(new \Awsan\AuthTracker\Events\SessionRevoked($login, RevokeLogin::REASON_USER));
        }

        $session->forget([Login::SESSION_KEY, self::SESSION_ID_KEY]);
        $this->tracker->sessions()->forgetCurrent();
    }

    protected function bindLoginToSession(Login $login, Session $session): void
    {
        $session->put(Login::SESSION_KEY, $login->getKey());
        $session->put(self::SESSION_ID_KEY, $session->getId());
        $this->tracker->sessions()->forgetCurrent();
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
            Failed::class => 'handleFailed',
            Lockout::class => 'handleLockout',
        ];
    }
}
