<?php

namespace Awsan\AuthTracker;

use Awsan\AuthTracker\Actions\RevokeLogin;
use Awsan\AuthTracker\QueryBuilders\ExpirableEloquentQueryBuilder;

class EloquentQueryBuilder extends ExpirableEloquentQueryBuilder
{
    /**
     * Scope to the logins that are not revoked.
     */
    public function active(): static
    {
        return $this->whereNull('revoked_at');
    }

    /**
     * Scope to the revoked logins.
     */
    public function revoked(): static
    {
        return $this->withExpired()->whereNotNull('revoked_at');
    }

    /**
     * Revoke the logins matching the query: destroy the sessions, revoke
     * the tokens and mark the logins as revoked (kept for history).
     *
     * @return int  Number of revoked logins.
     */
    public function revoke(string $reason = RevokeLogin::REASON_USER): int
    {
        return app(RevokeLogin::class)->many($this->get(), $reason);
    }
}
