<?php

namespace Awsan\AuthTracker\Exceptions;

/**
 * A refresh token that was already rotated was presented again: either
 * the legitimate client or an attacker holds a stolen copy. The whole
 * login is revoked.
 */
class RefreshTokenReusedException extends InvalidRefreshTokenException
{
    public function __construct()
    {
        parent::__construct('The refresh token was already used. The session has been revoked.', 'reused');
    }
}
