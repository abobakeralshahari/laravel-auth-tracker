<?php

namespace Alshahari\AuthTracker\Listeners;

use Alshahari\AuthTracker\AuthTracker;
use Alshahari\AuthTracker\Events\Login as LoginTracked;
use Alshahari\AuthTracker\Factories\LoginFactory;
use Alshahari\AuthTracker\RequestContext;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Events\Dispatcher;
use Laravel\Passport\Events\AccessTokenCreated;
use Laravel\Passport\Passport;
use Throwable;

/**
 * Tracks Passport access tokens.
 */
class PassportEventSubscriber
{
    public function handleAccessTokenCreation(AccessTokenCreated $event): void
    {
        $user = $this->resolveUser($event);

        if (! $user || ! AuthTracker::isTracked($user)) {
            return;
        }

        $context = new RequestContext;

        $login = LoginFactory::build($event, $context);

        $login->expiresAt(Passport::token()->newQuery()->find($event->tokenId)?->expires_at);

        $user->logins()->save($login);

        $this->attachDevice($context, $user);

        if (request()->input('grant_type') !== 'refresh_token') {
            event(new LoginTracked($user, $context));
        }
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

    protected function attachDevice(RequestContext $context, Authenticatable $user): void
    {
        if (! $context->device) {
            return;
        }

        try {
            $context->device->deviceable()->associate($user);
            $context->device->save();
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            AccessTokenCreated::class => 'handleAccessTokenCreation',
        ];
    }
}
