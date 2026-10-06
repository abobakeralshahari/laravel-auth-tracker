<?php

namespace OwaisKit\AuthTracker\Events;

use OwaisKit\AuthTracker\Models\Login;

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
