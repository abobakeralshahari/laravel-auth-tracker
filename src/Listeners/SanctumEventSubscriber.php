<?php

namespace OwaisKit\AuthTracker\Listeners;

use OwaisKit\AuthTracker\Actions\RecordLogin;
use OwaisKit\AuthTracker\Events\PersonalAccessTokenCreated;
use OwaisKit\AuthTracker\RequestContext;
use OwaisKit\AuthTracker\Support\Credential;
use OwaisKit\AuthTracker\TrackerManager;
use Carbon\Carbon;
use Illuminate\Events\Dispatcher;

/**
 * Tracks Sanctum personal access tokens.
 */
class SanctumEventSubscriber
{
    public function __construct(
        protected TrackerManager $tracker,
        protected RecordLogin $recorder,
    ) {
    }

    public function handlePersonalAccessTokenCreation(PersonalAccessTokenCreated $event): void
    {
        $token = $event->personalAccessToken;
        $user = $token->tokenable;

        if (! $user || ! $this->tracker->isTracked($user)) {
            return;
        }

        $expiresAt = $token->expires_at
            ?? (($minutes = config('sanctum.expiration')) ? Carbon::now()->addMinutes((int) $minutes) : null);

        $credential = Credential::sanctum($token->getKey(), $expiresAt);

        $this->recorder->execute($user, $credential, new RequestContext, $this->tracker->guardForDriver('sanctum'));
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
