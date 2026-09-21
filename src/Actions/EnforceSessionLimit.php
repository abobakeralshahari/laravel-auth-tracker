<?php

namespace Alshahari\AuthTracker\Actions;

use Alshahari\AuthTracker\Events\SessionLimitExceeded;
use Alshahari\AuthTracker\Exceptions\SessionLimitExceededException;
use Alshahari\AuthTracker\Models\Login;
use Alshahari\AuthTracker\TrackerManager;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;

/**
 * Apply the "max_sessions" policy of the trackable after a new login.
 *
 *   revoke_oldest  the oldest sessions beyond the limit are revoked
 *   reject         the new login is revoked and an exception is thrown
 *   ask            SessionLimitExceeded is dispatched, nothing is revoked
 */
class EnforceSessionLimit
{
    public const REVOKE_OLDEST = 'revoke_oldest';

    public const REJECT = 'reject';

    public const ASK = 'ask';

    public function __construct(
        protected TrackerManager $tracker,
        protected RevokeLogin $revoker,
    ) {
    }

    public function execute(Authenticatable $user, Login $login): void
    {
        $config = $this->tracker->trackable($user);
        $limit = (int) ($config['max_sessions'] ?? 0);

        if ($limit <= 0) {
            return;
        }

        $action = $config['on_exceed'] ?? self::REVOKE_OLDEST;
        $scope = $config['scope'] ?? 'per_guard';

        // Serialize concurrent logins of the same user. The decision is
        // taken under lock; revocations happen once the lock is released so
        // that the "reject" exception does not roll them back.
        $excess = DB::connection($login->getConnectionName())->transaction(function () use ($user, $login, $limit, $scope) {
            $active = $user->logins()
                ->active()
                ->when($scope === 'per_guard' && $login->guard, fn ($query) => $query->where('guard', $login->guard))
                ->lockForUpdate()
                ->orderBy('last_activity_at')
                ->orderBy('id')
                ->get();

            if ($active->count() <= $limit) {
                return null;
            }

            return $active->reject(fn (Login $candidate) => $candidate->is($login))
                ->take($active->count() - $limit)
                ->values();
        });

        if ($excess === null) {
            return;
        }

        event(new SessionLimitExceeded($user, $login, $excess, $limit, $action));

        match ($action) {
            self::REVOKE_OLDEST => $this->revoker->many($excess, RevokeLogin::REASON_LIMIT),
            self::REJECT => $this->reject($user, $login, $limit),
            default => null,
        };
    }

    protected function reject(Authenticatable $user, Login $login, int $limit): void
    {
        $this->revoker->execute($login, RevokeLogin::REASON_LIMIT);

        throw new SessionLimitExceededException($user, $login, $limit);
    }
}
