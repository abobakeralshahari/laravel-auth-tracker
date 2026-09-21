<?php

namespace Alshahari\AuthTracker;

use Alshahari\AuthTracker\QueryBuilders\ExpirableEloquentQueryBuilder;
use Alshahari\AuthTracker\Traits\ManagesLogins;

class EloquentQueryBuilder extends ExpirableEloquentQueryBuilder
{
    use ManagesLogins;

    /**
     * Revoke the logins matching the query: destroy the sessions, revoke
     * the tokens and mark the logins as cleared (kept for history).
     *
     * @return int  Number of revoked logins.
     */
    public function revoke()
    {
        $logins = $this->get();

        if ($logins->isEmpty()) {
            return 0;
        }

        foreach ($logins->pluck('session_id')->filter() as $sessionId) {
            $this->destroySession($sessionId);
        }

        $this->revokePassportTokens($logins->pluck('oauth_access_token_id')->filter());
        $this->revokeSanctumTokens($logins->pluck('personal_access_token_id')->filter());

        return $this->model->newQueryWithoutScopes()
            ->whereKey($logins->modelKeys())
            ->update(['cleared_by_user' => true, 'logout_at' => now(), 'remember_token' => null]);
    }
}
