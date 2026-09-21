<?php

namespace Alshahari\AuthTracker\Events;

use Alshahari\AuthTracker\Models\Login;

/**
 * A tracked login was revoked (user logout, "logout others", admin...).
 */
class SessionRevoked
{
    public function __construct(
        public Login $login,
        public string $reason,
    ) {
    }
}
