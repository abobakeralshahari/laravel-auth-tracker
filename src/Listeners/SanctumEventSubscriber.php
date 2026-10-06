<?php

namespace Awsan\AuthTracker\Listeners;

use Awsan\AuthTracker\Actions\RecordLogin;
use Awsan\AuthTracker\Events\PersonalAccessTokenCreated;
use Awsan\AuthTracker\RequestContext;
use Awsan\AuthTracker\Support\Credential;
use Awsan\AuthTracker\TrackerManager;
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
