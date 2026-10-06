<?php

namespace OwaisKit\AuthTracker\Drivers;

use OwaisKit\AuthTracker\Contracts\TrackerDriver;
use OwaisKit\AuthTracker\Models\Login;
use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Sanctum\Contracts\HasAbilities;
use Laravel\Sanctum\Sanctum;

class SanctumDriver implements TrackerDriver
{
    public function name(): string
    {
        return 'sanctum';
    }

    public function legacyColumn(): ?string
    {
        return 'personal_access_token_id';
    }

    public function currentCredentialId(Authenticatable $user): ?string
    {
        if (! method_exists($user, 'currentAccessToken')) {
            return null;
        }

        $token = $user->currentAccessToken();

        // A TransientToken means the user was authenticated by the session
        // (SPA), which is tracked by the session driver.
        if (! $token instanceof HasAbilities || ! method_exists($token, 'getKey')) {
            return null;
        }

        return (string) $token->getKey();
    }

    public function revoke(Login $login): void
    {
        if ($id = $login->credentialId()) {
            $model = Sanctum::$personalAccessTokenModel;

            $model::whereKey($id)->delete();
        }
    }
}
