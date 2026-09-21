<?php

namespace Alshahari\AuthTracker;

use Illuminate\Auth\EloquentUserProvider;

class EloquentUserProviderExtended extends EloquentUserProvider
{
    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * The remember tokens live in the logins table (one per session), so a
     * single session can be revoked without affecting the others.
     *
     * @param  mixed  $identifier
     * @param  string  $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByToken($identifier, #[\SensitiveParameter] $token)
    {
        $model = $this->createModel();

        $retrievedModel = $this->newModelQuery($model)->where(
            $model->getAuthIdentifierName(), $identifier
        )->first();

        if (! $retrievedModel || ! method_exists($retrievedModel, 'logins')) {
            return null;
        }

        $login = $retrievedModel->logins()
            ->where('remember_token', $token)
            ->whereNull('logout_at')
            ->where('cleared_by_user', false)
            ->first();

        return $login && hash_equals((string) $login->remember_token, (string) $token)
            ? $retrievedModel
            : null;
    }
}
