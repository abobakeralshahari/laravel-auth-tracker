<?php

namespace Alshahari\AuthTracker\Listeners;

use Alshahari\AuthTracker\Factories\LoginFactory;
use Alshahari\AuthTracker\RequestContext;
use Carbon\Carbon;
use Laravel\Passport\Events\AccessTokenCreated;
use Laravel\Passport\Token;
use mysql_xdevapi\Exception;

class PassportEventSubscriber
{
    public function handleAccessTokenCreation(AccessTokenCreated $event)
    {


        // Get the created access token
        $accessToken = Token::find($event->tokenId);
        // Get the authenticated user
        $provider = config('auth.guards.api.provider');
        $userModel = config('auth.providers.' . $provider . '.model');
        $user = call_user_func([$userModel, 'find'], $accessToken->user_id);

        if ($this->tracked($user)) {
            // Get as much information as possible about the request
            $context = new RequestContext;
            // Build a new login
            $login = LoginFactory::build($event, $context);
            // Set the expiration date
            $login->expiresAt($accessToken->expires_at);
            // Attach the login to the user and save it
            $user->logins()->save($login);

            try {
                if ($context->device) {
                    $context->device->deviceable()->associate($user);
                    $context->device->save();
                }
            } catch (Exception $E) {
                report($e);
            }

            if (request()->input('grant_type') !== 'refresh_token') {
                event(new \Alshahari\AuthTracker\Events\Login($user, $context));
            }
        }

    }

    public function handleSuccessfulLogout($event)
    {
        if ($this->tracked($event->user)) {

            // Delete login
            //            $event->user->logins()->where('session_id', session()->getId())
            //                ->delete();

            $date=Carbon::now()->toDateTimeString();
            $event->user->logins()->where('oauth_access_token_id', session()->getId())
                ->update(['cleared_by_user'=>true,'logout_at'=>$date]);
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
        if ($user) {
            return in_array('Alshahari\AuthTracker\Traits\AuthTracking', class_uses($user));
        }
        return false;
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param \Illuminate\Events\Dispatcher $events
     */
    public function subscribe($events)
    {

        $events->listen(
            'Laravel\Passport\Events\AccessTokenCreated',
            'Alshahari\AuthTracker\Listeners\PassportEventSubscriber@handleAccessTokenCreation'
        );
    }
}
