<?php

namespace Alshahari\AuthTracker\Listeners;

use Alshahari\AuthTracker\AuthTracker;
use Alshahari\AuthTracker\Events\Login as LoginTracked;
use Alshahari\AuthTracker\Events\PersonalAccessTokenCreated;
use Alshahari\AuthTracker\Factories\LoginFactory;
use Alshahari\AuthTracker\RequestContext;
use Carbon\Carbon;
use Illuminate\Events\Dispatcher;

/**
 * Tracks Sanctum personal access tokens.
 */
class SanctumEventSubscriber
{
    public function handlePersonalAccessTokenCreation(PersonalAccessTokenCreated $event): void
    {
        $user = $event->personalAccessToken->tokenable;

        if (! $user || ! AuthTracker::isTracked($user)) {
            return;
        }

        $context = new RequestContext;

        $login = LoginFactory::build($event, $context);

        if ($expiresAt = $event->personalAccessToken->expires_at) {
            $login->expiresAt($expiresAt);
        } elseif ($minutes = config('sanctum.expiration')) {
            $login->expiresAt(Carbon::now()->addMinutes((int) $minutes));
        }

        $user->logins()->save($login);

        if ($context->device) {
            $context->device->deviceable()->associate($user);
            $context->device->save();
        }

        event(new LoginTracked($user, $context));
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            PersonalAccessTokenCreated::class => 'handlePersonalAccessTokenCreation',
        ];
    }
}
