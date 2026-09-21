<?php

namespace Alshahari\AuthTracker\Traits;

use Alshahari\AuthTracker\AuthTracker;
use Alshahari\AuthTracker\Models\Login;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait AuthTracking
{
    /**
     * Get all of the user's logins.
     */
    public function logins(): MorphMany
    {
        return $this->morphMany(AuthTracker::loginModel(), 'authenticatable');
    }

    /**
     * Get the login of the current request.
     */
    public function currentLogin(): ?Login
    {
        if ($this->isAuthenticatedByPassport()) {
            return $this->logins()
                ->where('oauth_access_token_id', $this->currentPassportTokenId())
                ->first();
        }

        if ($this->isAuthenticatedBySanctum()) {
            return $this->logins()
                ->where('personal_access_token_id', $this->currentAccessToken()->getKey())
                ->first();
        }

        if ($this->isAuthenticatedBySession()) {
            $session = request()->session();

            if ($session->has(Login::SESSION_KEY)) {
                return $this->logins()->find($session->get(Login::SESSION_KEY));
            }

            return $this->logins()->where('session_id', $session->getId())->first();
        }

        return null;
    }

    /**
     * Destroy a session / Revoke an access token by its ID.
     *
     * @param  int|string|null  $loginId  Null for the current login.
     */
    public function logout($loginId = null): bool
    {
        $login = $loginId ? $this->logins()->find($loginId) : $this->currentLogin();

        return $login ? (bool) $login->revoke() : false;
    }

    /**
     * Destroy all sessions / Revoke all access tokens, except the current one.
     */
    public function logoutOthers(): bool
    {
        $current = $this->currentLogin();

        $logins = $this->activeLoginsQuery()
            ->when($current, fn (Builder $query) => $query->whereKeyNot($current->getKey()))
            ->get();

        return $this->revokeLogins($logins);
    }

    /**
     * Destroy all sessions / Revoke all access tokens.
     */
    public function logoutAll(): bool
    {
        return $this->revokeLogins($this->activeLoginsQuery()->get());
    }

    /**
     * Determine if current user is authenticated via a session.
     */
    public function isAuthenticatedBySession(): bool
    {
        $request = app()->bound('request') ? request() : null;

        return $request && $request->hasSession()
            && ($request->session()->has(Login::SESSION_KEY) || $this->isCurrentUser());
    }

    /**
     * Check for authentication via Passport.
     */
    public function isAuthenticatedByPassport(): bool
    {
        return $this->usesTrait('Laravel\Passport\HasApiTokens')
            && ! is_null($this->currentPassportTokenId());
    }

    /**
     * Check for authentication via Sanctum.
     */
    public function isAuthenticatedBySanctum(): bool
    {
        return $this->usesTrait('Laravel\Sanctum\HasApiTokens')
            && ! is_null($this->currentAccessToken());
    }

    /**
     * Id of the Passport access token used by the current request, if any.
     */
    public function currentPassportTokenId(): ?string
    {
        if (! $this->usesTrait('Laravel\Passport\HasApiTokens')) {
            return null;
        }

        $token = $this->token();

        if (! $token && $this->isCurrentUser() && method_exists(auth()->user(), 'token')) {
            $token = auth()->user()->token();
        }

        return $token ? (string) $token->getKey() : null;
    }

    /**
     * Active logins (not revoked by the user) with their device.
     */
    public function activeLogin(): Collection
    {
        return $this->activeLoginsQuery()
            ->with('device:id,udid,os,os_version,manufacturer,model,app_version,app_type')
            ->orderByDesc('updated_at')
            ->get();
    }

    /**
     * Revoked logins.
     */
    public function historyLogin(): Collection
    {
        return $this->logins()
            ->withExpired()
            ->whereNotNull('logout_at')
            ->where('cleared_by_user', true)
            ->with('device:id,os,os_version,manufacturer,model,app_version,app_type')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Delete one revoked login from the history.
     */
    public function clearHistoryId($id): int
    {
        return $this->logins()
            ->withExpired()
            ->whereKey($id)
            ->whereNotNull('logout_at')
            ->where('cleared_by_user', true)
            ->delete();
    }

    /**
     * Delete all revoked logins from the history.
     */
    public function clearHistory(): int
    {
        return $this->logins()
            ->withExpired()
            ->whereNotNull('logout_at')
            ->where('cleared_by_user', true)
            ->delete();
    }

    /**
     * Query of the active logins.
     */
    protected function activeLoginsQuery(): MorphMany
    {
        return $this->logins()
            ->whereNull('logout_at')
            ->where('cleared_by_user', false);
    }

    protected function revokeLogins(Collection $logins): bool
    {
        $logins->each->revoke();

        return $logins->isNotEmpty();
    }

    protected function usesTrait(string $trait): bool
    {
        return in_array($trait, class_uses_recursive($this), true);
    }

    /**
     * Is this model the authenticated user of the current request?
     */
    protected function isCurrentUser(): bool
    {
        $user = auth()->user();

        return $user && $user->is($this);
    }
}
