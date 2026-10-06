<?php

namespace Awsan\AuthTracker\Support;

use Carbon\CarbonInterface;

/**
 * The credential produced by an authentication: which driver issued it,
 * its identifier and when it expires.
 */
final class Credential
{
    public function __construct(
        public readonly string $driver,
        public readonly string $id,
        public readonly ?CarbonInterface $expiresAt = null,
        public readonly ?string $rememberToken = null,
    ) {
    }

    public static function session(string $id, ?CarbonInterface $expiresAt, ?string $rememberToken = null): self
    {
        return new self('session', $id, $expiresAt, $rememberToken);
    }

    public static function sanctum(string|int $id, ?CarbonInterface $expiresAt): self
    {
        return new self('sanctum', (string) $id, $expiresAt);
    }

    public static function passport(string $id, ?CarbonInterface $expiresAt): self
    {
        return new self('passport', $id, $expiresAt);
    }
}
