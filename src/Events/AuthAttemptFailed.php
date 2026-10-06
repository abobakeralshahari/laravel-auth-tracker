<?php

namespace OwaisKit\AuthTracker\Events;

use OwaisKit\AuthTracker\Models\AuthAttempt;

class AuthAttemptFailed
{
    public function __construct(public AuthAttempt $attempt)
    {
    }
}
