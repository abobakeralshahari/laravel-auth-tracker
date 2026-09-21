<?php

namespace Alshahari\AuthTracker\Events;

use Alshahari\AuthTracker\Models\AuthAttempt;

class AuthAttemptFailed
{
    public function __construct(public AuthAttempt $attempt)
    {
    }
}
