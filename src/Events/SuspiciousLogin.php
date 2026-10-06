<?php

namespace Awsan\AuthTracker\Events;

use Awsan\AuthTracker\Models\Login;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * A login raised risk flags (new device, new country, impossible travel).
 * The application decides what to do: notify, require 2FA, block...
 */
class SuspiciousLogin
{
    /**
     * @param  list<string>  $flags
     */
    public function __construct(
        public Authenticatable $user,
        public Login $login,
        public array $flags,
        public int $score,
        public ?Login $previous = null,
    ) {
    }

    public function has(string $flag): bool
    {
        return in_array($flag, $this->flags, true);
    }
}
