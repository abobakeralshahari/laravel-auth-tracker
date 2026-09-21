<?php

namespace Alshahari\AuthTracker\Drivers;

use Alshahari\AuthTracker\Contracts\TrackerDriver;
use Alshahari\AuthTracker\Models\Login;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Auth;

class SessionDriver implements TrackerDriver
{
    public function name(): string
    {
        return 'session';
    }

    public function legacyColumn(): ?string
    {
        return 'session_id';
    }

    public function currentCredentialId(Authenticatable $user): ?string
    {
        $session = $this->session();

        if (! $session) {
            return null;
        }

        $current = Auth::user();

        return $current && $current->is($user) ? $session->getId() : null;
    }

    /**
     * Id of the login bound to the current session, if any.
     */
    public function currentLoginId(): int|string|null
    {
        return $this->session()?->get(Login::SESSION_KEY);
    }

    public function revoke(Login $login): void
    {
        if (! $login->credentialId()) {
            return;
        }

        $session = $this->session();

        if ($session && $login->credentialId() === $session->getId()) {
            Auth::logout();
            $session->invalidate();

            return;
        }

        session()->getHandler()->destroy($login->credentialId());
    }

    protected function session(): ?Session
    {
        $request = app()->bound('request') ? request() : null;

        return $request && $request->hasSession() ? $request->session() : null;
    }
}
