<?php

namespace Alshahari\AuthTracker\Events;

use Alshahari\AuthTracker\Models\Login;

/**
 * The credential of a login was replaced (token refresh).
 */
class SessionRotated
{
    public function __construct(
        public Login $login,
        public string $previousCredentialId,
    ) {
    }
}
