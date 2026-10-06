<?php

namespace Awsan\AuthTracker\Exceptions;

use Awsan\AuthTracker\Models\Login;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Thrown by the "reject" session limit policy: the new login was revoked
 * because the user already has the maximum number of active sessions.
 */
class SessionLimitExceededException extends RuntimeException
{
    public function __construct(
        public readonly Authenticatable $user,
        public readonly Login $login,
        public readonly int $limit,
    ) {
        parent::__construct("You already have {$limit} active session(s). Log out from another device first.");
    }

    public function render(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'code' => 'session_limit_exceeded',
                'limit' => $this->limit,
            ], 409);
        }

        return back()->withErrors(['session' => $this->getMessage()]);
    }
}
