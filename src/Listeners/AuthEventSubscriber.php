<?php

namespace Alshahari\AuthTracker\Listeners;

use Alshahari\AuthTracker\Factories\LoginFactory;
use Alshahari\AuthTracker\Models\Login;
use Alshahari\AuthTracker\RequestContext;
use Carbon\Carbon;
use Illuminate\Auth\Events\Login as LoginEvent;
use Illuminate\Auth\Recaller;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuthEventSubscriber
{
    public function handleSuccessfulLogin(LoginEvent $event)
    {

        if ($this->tracked($event->user) && request()->hasSession() ) {

            if (Auth::viaRemember()) {
                // Logged in via remember token

                if (!is_null($recaller = $this->recaller())) {

                    // Update session id
                    Login::where('remember_token', $recaller->token())->update([
                        'session_id' => session()->getId()
                    ]);
                }
            } else {
                // Initial login

                // Regenerate the session ID to avoid session fixation attacks
                session()->regenerate();
                // Get as much information as possible about the request
                $context = new RequestContext;

                // Build a new login
                $login = LoginFactory::build($event, $context);

                // Set the expiration date based on whether it is a remembered login or not
                if ($event->remember) {
                    $login->expiresAt(Carbon::now()->addDays(config('auth_tracker.remember_lifetime', 365)));
                } else {
                    $login->expiresAt(Carbon::now()->addMinutes(config('session.lifetime')));
                }

                // Attach the login to the user and save it
                $event->user->logins()->save($login);

                // Update the remember token
                $this->updateRememberToken($event->user, Str::random(60));

                event(new \Alshahari\AuthTracker\Events\Login($event->user, $context));
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
            $event->user->logins()->where('session_id', session()->getId())
             ->update(['cleared_by_user'=>true,'logout_at'=>$date]);
        }
    }

    /**
     * Get the decrypted recaller cookie for the request.
     *
     * @return Recaller|null
     */
    protected function recaller()
    {
        if (is_null(request())) {
            return null;
        }

        if ($recaller = request()->cookies->get(Auth::guard()->getRecallerName())) {
            return new Recaller($recaller);
        }

        return null;
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param  Authenticatable  $user
     * @param  string  $token
     * @return void
     */
    protected function updateRememberToken(Authenticatable $user, $token)
    {
        $user->setRememberToken($token);
        $user->timestamps = false;
        $user->save();
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
            'Illuminate\Auth\Events\Login',
            'Alshahari\AuthTracker\Listeners\AuthEventSubscriber@handleSuccessfulLogin'
        );

        $events->listen(
            'Illuminate\Auth\Events\Logout',
            'Alshahari\AuthTracker\Listeners\AuthEventSubscriber@handleSuccessfulLogout'
        );
    }
}
