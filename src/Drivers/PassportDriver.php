<?php

namespace Awsan\AuthTracker\Drivers;

use Awsan\AuthTracker\Contracts\TrackerDriver;
use Awsan\AuthTracker\Models\Login;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PassportDriver implements TrackerDriver
{
    public function name(): string
    {
        return 'passport';
    }

    public function legacyColumn(): ?string
    {
        return 'oauth_access_token_id';
    }

    public function currentCredentialId(Authenticatable $user): ?string
    {
        if (! method_exists($user, 'token')) {
            return null;
        }

        $token = $user->token();

        // The token is only set on the instance resolved by the guard.
        if (! $token && ($current = Auth::user()) && $current->is($user) && method_exists($current, 'token')) {
            $token = $current->token();
        }

        return $token ? (string) $token->getKey() : null;
    }

    public function revoke(Login $login): void
    {
        if (! $id = $login->credentialId()) {
            return;
        }

        $connection = DB::connection(config('auth_tracker.connection'));

        $connection->table('oauth_refresh_tokens')->where('access_token_id', $id)->update(['revoked' => true]);
        $connection->table('oauth_access_tokens')->where('id', $id)->update(['revoked' => true]);
    }
}
