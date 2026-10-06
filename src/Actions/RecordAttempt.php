<?php

namespace Awsan\AuthTracker\Actions;

use Awsan\AuthTracker\Events\AuthAttemptFailed;
use Awsan\AuthTracker\Models\AuthAttempt;
use Illuminate\Http\Request;

/**
 * Store a failed authentication attempt.
 */
class RecordAttempt
{
    public function execute(array $credentials, string $reason, ?string $guard = null, ?Request $request = null): ?AuthAttempt
    {
        if (! config('auth_tracker.attempts.enabled', true)) {
            return null;
        }

        $request = $request ?? (app()->bound('request') ? request() : null);

        $attempt = AuthAttempt::create([
            'identifier' => $this->identifier($credentials),
            'guard' => $guard,
            'reason' => $reason,
            'device_id' => $request ? ResolveDevice::current($request)?->getKey() : null,
            'ip' => $request?->ip(),
            'user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 1000) : null,
            'attempted_at' => now(),
        ]);

        event(new AuthAttemptFailed($attempt));

        return $attempt;
    }

    protected function identifier(array $credentials): ?string
    {
        foreach ((array) config('auth_tracker.attempts.identifier_keys', ['email']) as $key) {
            if (! empty($credentials[$key]) && is_scalar($credentials[$key])) {
                return mb_substr((string) $credentials[$key], 0, 191);
            }
        }

        return null;
    }
}
