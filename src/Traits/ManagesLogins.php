<?php

namespace Alshahari\AuthTracker\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

trait ManagesLogins
{
    /**
     * Destroy the given session id.
     *
     * @param  string  $sessionId
     * @return void
     */
    protected function destroySession($sessionId)
    {
        $request = app()->bound('request') ? request() : null;

        if ($request && $request->hasSession() && $sessionId === $request->session()->getId()) {
            Auth::logout();
            $request->session()->invalidate();

            return;
        }

        session()->getHandler()->destroy($sessionId);
    }

    /**
     * Revoke the given Passport access token ids.
     *
     * @param  Collection|array|string  $accessTokenIds
     * @return void
     */
    protected function revokePassportTokens($accessTokenIds)
    {
        $accessTokenIds = $this->normalizeIds($accessTokenIds, func_get_args());

        if (empty($accessTokenIds)) {
            return;
        }

        $connection = DB::connection(config('auth_tracker.connection'));

        $connection->table('oauth_refresh_tokens')
            ->whereIn('access_token_id', $accessTokenIds)
            ->update(['revoked' => true]);

        $connection->table('oauth_access_tokens')
            ->whereIn('id', $accessTokenIds)
            ->update(['revoked' => true]);
    }

    /**
     * Revoke the given Sanctum personal access token ids.
     *
     * @param  Collection|array|int  $personalAccessTokenIds
     * @return void
     */
    protected function revokeSanctumTokens($personalAccessTokenIds)
    {
        $personalAccessTokenIds = $this->normalizeIds($personalAccessTokenIds, func_get_args());

        if (empty($personalAccessTokenIds) || ! class_exists(\Laravel\Sanctum\Sanctum::class)) {
            return;
        }

        $model = \Laravel\Sanctum\Sanctum::$personalAccessTokenModel;

        $model::whereIn('id', $personalAccessTokenIds)->delete();
    }

    /**
     * Accept a collection, an array or a variadic list of ids.
     */
    private function normalizeIds($ids, array $args): array
    {
        if ($ids instanceof Collection) {
            return $ids->all();
        }

        return is_array($ids) ? $ids : $args;
    }
}
