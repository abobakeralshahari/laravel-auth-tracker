<?php

namespace Awsan\AuthTracker\Actions;

use Awsan\AuthTracker\Drivers\SessionDriver;
use Awsan\AuthTracker\TrackerManager;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;

/**
 * Record the activity of the current login, at most once per interval, so
 * that an authenticated request costs neither a query nor a write.
 */
class TouchActivity
{
    public function __construct(protected TrackerManager $tracker)
    {
    }

    public function execute(Authenticatable $user): bool
    {
        $key = $this->currentCredentialKey($user);

        if (! $key || ! $this->isDue($key)) {
            return false;
        }

        $login = $this->tracker->current($user);

        if (! $login) {
            return false;
        }

        $login->newQueryWithoutScopes()
            ->whereKey($login->getKey())
            ->update(['last_activity_at' => now()]);

        return true;
    }

    /**
     * A cache key identifying the credential of the current request,
     * computed without hitting the database.
     */
    protected function currentCredentialKey(Authenticatable $user): ?string
    {
        foreach ($this->tracker->driverNames() as $name) {
            $driver = $this->tracker->driver($name);

            if ($driver instanceof SessionDriver && ($id = $driver->currentLoginId())) {
                return 'login:'.$id;
            }

            if ($id = $driver->currentCredentialId($user)) {
                return $name.':'.sha1($id);
            }
        }

        return null;
    }

    protected function isDue(string $key): bool
    {
        $interval = (int) config('auth_tracker.activity.touch_interval', 60);

        return $interval <= 0 || Cache::add('auth_tracker:touch:'.$key, 1, $interval);
    }
}
