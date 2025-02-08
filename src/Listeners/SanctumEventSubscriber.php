<?php

namespace abobakerMohsan\AuthTracker\Listeners;

use abobakerMohsan\AuthTracker\Events\PersonalAccessTokenCreated;
use abobakerMohsan\AuthTracker\Factories\LoginFactory;
use abobakerMohsan\AuthTracker\RequestContext;
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

            event(new \abobakerMohsan\AuthTracker\Events\Login($user, $context));
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
        return in_array('abobakerMohsan\AuthTracker\Traits\AuthTracking', class_uses($user));
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     */
    public function subscribe($events)
    {
        $events->listen(
            'abobakerMohsan\AuthTracker\Events\PersonalAccessTokenCreated',
            'abobakerMohsan\AuthTracker\Listeners\SanctumEventSubscriber@handlePersonalAccessTokenCreation'
        );
    }
}
