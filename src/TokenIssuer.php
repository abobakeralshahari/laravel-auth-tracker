<?php

namespace Alshahari\AuthTracker;

use Alshahari\AuthTracker\Actions\RecordLogin;
use Alshahari\AuthTracker\Actions\RevokeLogin;
use Alshahari\AuthTracker\Exceptions\InvalidRefreshTokenException;
use Alshahari\AuthTracker\Exceptions\RefreshTokenReusedException;
use Alshahari\AuthTracker\Models\Login;
use Alshahari\AuthTracker\Support\Credential;
use Alshahari\AuthTracker\Support\IssuedToken;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use LogicException;

/**
 * Short lived Sanctum access tokens renewed with a rotating refresh token.
 *
 * The refresh token is bound to the tracked login: revoking the login
 * (user, admin, "logout others") makes the refresh fail immediately.
 * A refresh token presented twice reveals a theft and revokes the login
 * (OAuth 2.1 refresh token rotation).
 */
class TokenIssuer
{
    public function __construct(
        protected TrackerManager $tracker,
        protected RecordLogin $recorder,
        protected RevokeLogin $revoker,
    ) {
    }

    /**
     * Issue an access token + refresh token for the user.
     *
     * @param  list<string>  $abilities
     */
    public function issue(Authenticatable $user, string $name = 'api', array $abilities = ['*'], ?string $guard = null): IssuedToken
    {
        $this->ensureSanctum($user);

        $accessExpiresAt = now()->addMinutes((int) config('auth_tracker.refresh.access_lifetime', 60));
        $refreshExpiresAt = now()->addMinutes((int) config('auth_tracker.refresh.refresh_lifetime', 60 * 24 * 30));

        $token = $user->createToken($name, $abilities, $accessExpiresAt);

        $login = $this->recorder->execute(
            $user,
            Credential::sanctum($token->accessToken->getKey(), $refreshExpiresAt),
            new RequestContext,
            $guard ?? $this->tracker->guardForDriver('sanctum'),
        );

        $refresh = $this->rotateRefreshToken($login, $refreshExpiresAt);

        return new IssuedToken($token->plainTextToken, $refresh, $accessExpiresAt, $refreshExpiresAt, $login);
    }

    /**
     * Exchange a refresh token for a new access token + refresh token.
     *
     * @throws InvalidRefreshTokenException
     */
    public function refresh(string $refreshToken): IssuedToken
    {
        $hash = hash('sha256', $refreshToken);
        $model = $this->tracker->loginModel();

        /** @var Login|null $login */
        $login = $model::withExpired()->where('refresh_token_hash', $hash)->first();

        if (! $login) {
            $this->detectReuse($model, $hash);

            throw new InvalidRefreshTokenException;
        }

        if ($login->isRevoked() || ! $login->refresh_expires_at || $login->refresh_expires_at->isPast()) {
            throw new InvalidRefreshTokenException(reason: $login->isRevoked() ? 'revoked' : 'expired');
        }

        return DB::connection($login->getConnectionName())->transaction(function () use ($login, $hash) {
            // Re-read under lock: a concurrent refresh with the same token
            // must see the rotation and fail as a reuse.
            $login = $login->newQueryWithoutScopes()->whereKey($login->getKey())->lockForUpdate()->first();

            if ($login->refresh_token_hash !== $hash) {
                throw new RefreshTokenReusedException;
            }

            $user = $login->authenticatable;

            if (! $user) {
                throw new InvalidRefreshTokenException(reason: 'orphan');
            }

            $previous = $this->currentToken($login);

            $accessExpiresAt = now()->addMinutes((int) config('auth_tracker.refresh.access_lifetime', 60));
            $refreshExpiresAt = now()->addMinutes((int) config('auth_tracker.refresh.refresh_lifetime', 60 * 24 * 30));

            $token = $user->createToken($previous?->name ?? 'api', $previous?->abilities ?? ['*'], $accessExpiresAt);

            $this->recorder->rotate($login, Credential::sanctum($token->accessToken->getKey(), $refreshExpiresAt));

            $previous?->delete();

            $refresh = $this->rotateRefreshToken($login, $refreshExpiresAt);

            return new IssuedToken($token->plainTextToken, $refresh, $accessExpiresAt, $refreshExpiresAt, $login);
        });
    }

    /**
     * Presenting the previous refresh token means it was stolen.
     */
    protected function detectReuse(string $model, string $hash): void
    {
        $login = $model::withExpired()->where('previous_refresh_token_hash', $hash)->first();

        if ($login) {
            $this->revoker->execute($login, RevokeLogin::REASON_SECURITY);

            throw new RefreshTokenReusedException;
        }
    }

    protected function rotateRefreshToken(Login $login, $refreshExpiresAt): string
    {
        $plain = Str::random(80);

        $login->forceFill([
            'previous_refresh_token_hash' => $login->refresh_token_hash,
            'refresh_token_hash' => hash('sha256', $plain),
            'refresh_expires_at' => $refreshExpiresAt,
        ])->save();

        return $plain;
    }

    protected function currentToken(Login $login)
    {
        $model = Sanctum::$personalAccessTokenModel;

        return $login->credentialId() ? $model::find($login->credentialId()) : null;
    }

    protected function ensureSanctum(Authenticatable $user): void
    {
        if (! class_exists(Sanctum::class) || ! method_exists($user, 'createToken')) {
            throw new LogicException('Issuing refreshable tokens requires Laravel Sanctum and the HasApiTokens trait.');
        }
    }
}
