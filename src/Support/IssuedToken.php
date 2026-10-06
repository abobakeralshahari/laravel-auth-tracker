<?php

namespace Awsan\AuthTracker\Support;

use Awsan\AuthTracker\Models\Login;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Support\Arrayable;

/**
 * An access token and the refresh token that renews it.
 */
final class IssuedToken implements Arrayable
{
    public function __construct(
        public readonly string $accessToken,
        public readonly string $refreshToken,
        public readonly CarbonInterface $accessExpiresAt,
        public readonly CarbonInterface $refreshExpiresAt,
        public readonly Login $login,
    ) {
    }

    public function toArray(): array
    {
        return [
            'token_type' => 'Bearer',
            'access_token' => $this->accessToken,
            'expires_in' => max(0, now()->diffInSeconds($this->accessExpiresAt, false)),
            'refresh_token' => $this->refreshToken,
            'refresh_expires_in' => max(0, now()->diffInSeconds($this->refreshExpiresAt, false)),
        ];
    }
}
