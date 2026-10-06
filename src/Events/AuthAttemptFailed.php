<?php

namespace Awsan\AuthTracker\Events;

use Awsan\AuthTracker\Models\AuthAttempt;

class AuthAttemptFailed
{
    public function __construct(public AuthAttempt $attempt)
    {
    }
}
