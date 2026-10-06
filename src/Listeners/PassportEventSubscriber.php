<?php

namespace OwaisKit\AuthTracker\Listeners;

use OwaisKit\AuthTracker\Actions\RecordLogin;
use OwaisKit\AuthTracker\RequestContext;
use OwaisKit\AuthTracker\Support\Credential;
use OwaisKit\AuthTracker\TrackerManager;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Events\Dispatcher;
use Laravel\Passport\Events\AccessTokenCreated;
use Laravel\Passport\Events\AccessTokenRevoked;
use Laravel\Passport\Passport;

/**
 * Tracks Passport access tokens.
 *
 * A token refresh does not create a new login: the login keeps its identity
 * and its credential is rotated to the new access token.
 */
class PassportEventSubscriber
{
    /**
     * Request attribute holding the id of the access token revoked by the
     * refresh grant, right before the new one is created.
     */
    const REFRESHED_TOKEN_KEY = 'auth_tracker.passport.refreshed_token';

    public function __construct(
        protected TrackerManager $tracker,
        protected RecordLogin $recorder,
    ) {
    }

    /**
     * The refresh grant revokes the previous access token before issuing
     * the new one: remember it to link both.
     */
    public function handleAccessTokenRevocation(AccessTokenRevoked $event): void
    {
        if ($this->isRefreshRequest()) {
            request()->attributes->set(self::REFRESHED_TOKEN_KEY, $event->tokenId);
        }
    }

    public function handleAccessTokenCreation(AccessTokenCreated $event): void
    {
        $user = $this->resolveUser($event);

        if (! $user || ! $this->tracker->isTracked($user)) {
            return;
        }

        $token = Passport::token()->newQuery()->find($event->tokenId);
        $credential = Credential::passport($event->tokenId, $token?->expires_at);

        if ($login = $this->refreshedLogin($user)) {
            $this->recorder->rotate($login, $credential);

            return;
        }

        $this->recorder->execute($user, $credential, new RequestContext, $this->tracker->guardForDriver('passport'));
    }

    /**
     * The login owning the access token that was just refreshed, if any.
     */
    protected function refreshedLogin(Authenticatable $user)
    {
        $previous = $this->isRefreshRequest() ? request()->attributes->get(self::REFRESHED_TOKEN_KEY) : null;

        if (! $previous) {
            return null;
        }

        return $user->logins()
            ->withExpired()
            ->active()
            ->where('driver', 'passport')
            ->where('credential_id', $previous)
            ->first();
    }

    /**
     * Resolve the owner of the token from the client's user provider, or
     * from the configured Passport guards.
     */
    protected function resolveUser(AccessTokenCreated $event): ?Authenticatable
    {
        if (! $event->userId) {
            return null; // client credentials grant
        }

        $client = Passport::client()->newQuery()->find($event->clientId);

        $providers = array_filter([$client?->provider]);

        foreach ((array) config('auth_tracker.passport_guards', ['api']) as $guard) {
            $providers[] = config("auth.guards.{$guard}.provider");
        }

        foreach (array_unique(array_filter($providers)) as $provider) {
            if ($model = config("auth.providers.{$provider}.model")) {
                return $model::query()->find($event->userId);
            }
        }

        return null;
    }

    protected function isRefreshRequest(): bool
    {
        return app()->bound('request') && request()->input('grant_type') === 'refresh_token';
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            AccessTokenRevoked::class => 'handleAccessTokenRevocation',
            AccessTokenCreated::class => 'handleAccessTokenCreation',
        ];
    }
}
