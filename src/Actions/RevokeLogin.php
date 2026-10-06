<?php

namespace OwaisKit\AuthTracker\Actions;

use OwaisKit\AuthTracker\Events\SessionRevoked;
use OwaisKit\AuthTracker\Models\Login;
use OwaisKit\AuthTracker\TrackerManager;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Revoke a login: invalidate its session / token and mark it as revoked.
 * The record is kept for the login history.
 */
class RevokeLogin
{
    public const REASON_USER = 'user';

    public const REASON_USER_ALL = 'user_all';

    public const REASON_ADMIN = 'admin';

    public const REASON_EXPIRED = 'expired';

    public const REASON_SECURITY = 'security';

    public const REASON_LIMIT = 'session_limit';

    public function __construct(protected TrackerManager $tracker)
    {
    }

    public function execute(Login $login, string $reason = self::REASON_USER): bool
    {
        if ($login->isRevoked()) {
            return false;
        }

        // Flag first: invalidating the current session fires Laravel's
        // Logout event, whose listener must not handle this login again.
        $login->forceFill([
            'revoked_at' => now(),
            'revoked_reason' => $reason,
            'remember_token' => null,
            // Legacy columns.
            'cleared_by_user' => true,
            'logout_at' => now(),
        ])->save();

        try {
            $this->tracker->driver($login->driverName())->revoke($login);
        } catch (Throwable $e) {
            report($e);
        }

        event(new SessionRevoked($login, $reason));

        return true;
    }

    /**
     * @param  Collection<int, Login>  $logins
     * @return int  Number of revoked logins.
     */
    public function many(Collection $logins, string $reason = self::REASON_USER): int
    {
        return $logins->reduce(fn (int $count, Login $login) => $count + (int) $this->execute($login, $reason), 0);
    }
}
