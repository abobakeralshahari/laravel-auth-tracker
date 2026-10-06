<?php

namespace OwaisKit\AuthTracker\Actions;

use OwaisKit\AuthTracker\Events\SuspiciousLogin;
use OwaisKit\AuthTracker\Models\Login;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Compare a new login with the history of the user and raise risk flags:
 * new device, new country, impossible travel.
 */
class AssessRisk
{
    public const NEW_DEVICE = 'new_device';

    public const NEW_COUNTRY = 'new_country';

    public const IMPOSSIBLE_TRAVEL = 'impossible_travel';

    public function execute(Authenticatable $user, Login $login): array
    {
        if (! config('auth_tracker.risk.enabled', true)) {
            return [];
        }

        $weights = (array) config('auth_tracker.risk.flags', []);
        $flags = [];

        $previous = $user->logins()
            ->withExpired()
            ->whereKeyNot($login->getKey())
            ->latest('id')
            ->first();

        if (! $previous) {
            return []; // First login ever: nothing to compare with.
        }

        if (isset($weights[self::NEW_DEVICE]) && $login->device_id && ! $this->knownDevice($user, $login)) {
            $flags[] = self::NEW_DEVICE;
        }

        if (isset($weights[self::NEW_COUNTRY]) && $login->country && ! $this->knownCountry($user, $login)) {
            $flags[] = self::NEW_COUNTRY;
        }

        if (isset($weights[self::IMPOSSIBLE_TRAVEL]) && $this->isImpossibleTravel($previous, $login)) {
            $flags[] = self::IMPOSSIBLE_TRAVEL;
        }

        $score = min(100, array_sum(array_map(fn ($flag) => (int) $weights[$flag], $flags)));

        $login->forceFill(['risk_score' => $score, 'risk_flags' => $flags ?: null])->saveQuietly();

        if ($flags && $score >= (int) config('auth_tracker.risk.threshold', 30)) {
            event(new SuspiciousLogin($user, $login, $flags, $score, $previous));
        }

        return $flags;
    }

    protected function knownDevice(Authenticatable $user, Login $login): bool
    {
        return $user->logins()->withExpired()
            ->whereKeyNot($login->getKey())
            ->where('device_id', $login->device_id)
            ->exists();
    }

    protected function knownCountry(Authenticatable $user, Login $login): bool
    {
        return $user->logins()->withExpired()
            ->whereKeyNot($login->getKey())
            ->where('country', $login->country)
            ->exists();
    }

    /**
     * Faster than a plane between two logins.
     */
    protected function isImpossibleTravel(Login $previous, Login $login): bool
    {
        if (! $previous->latitude || ! $previous->longitude || ! $login->latitude || ! $login->longitude) {
            return false;
        }

        $km = $this->haversine((float) $previous->latitude, (float) $previous->longitude, (float) $login->latitude, (float) $login->longitude);
        $hours = max($previous->created_at->diffInSeconds($login->created_at ?? now()), 1) / 3600;

        return ($km / $hours) > (float) config('auth_tracker.risk.max_speed', 900);
    }

    protected function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $radius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $radius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
