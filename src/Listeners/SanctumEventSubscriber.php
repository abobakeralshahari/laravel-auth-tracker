<?php

namespace Alshahari\AuthTracker\Listeners;

use Alshahari\AuthTracker\Events\PersonalAccessTokenCreated;
use Alshahari\AuthTracker\Factories\LoginFactory;
use Alshahari\AuthTracker\RequestContext;
use Carbon\Carbon;

class SanctumEventSubscriber
{
    public function handlePersonalAccessTokenCreation(PersonalAccessTokenCreated $event)
    {
        // Get the authenticated user
        $user = $event->personalAccessToken->tokenable;

        if ($this->tracked($user)) {

            // Get as much information as possible about the request
            $context = new RequestContext;

            // Build a new login
            $login = LoginFactory::build($event, $context);

            // Set the expiration date
            if ($minutes = config('sanctum.expiration')) {
                $login->expiresAt(Carbon::now()->addMinutes($minutes));
            }

            // Attach the login to the user and save it
            $user->logins()->save($login);

            event(new \Alshahari\AuthTracker\Events\Login($user, $context));
        }
    }

    /**
     * Tracking enabled for this user?
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @return bool
     */
    protected function tracked($user)
    {
        return in_array('Alshahari\AuthTracker\Traits\AuthTracking', class_uses($user));
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     */
    public function subscribe($events)
    {
        $events->listen(
            'alshahari\AuthTracker\Events\PersonalAccessTokenCreated',
            'alshahari\AuthTracker\Listeners\SanctumEventSubscriber@handlePersonalAccessTokenCreation'
        );
    }
}
