<?php

namespace Awsan\AuthTracker\Exceptions;

use Illuminate\Http\Request;
use RuntimeException;

class InvalidRefreshTokenException extends RuntimeException
{
    public function __construct(string $message = 'The refresh token is invalid or expired.', public readonly string $reason = 'invalid')
    {
        parent::__construct($message);
    }

    public function render(Request $request)
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => 'invalid_refresh_token',
            'reason' => $this->reason,
        ], 401);
    }
}
