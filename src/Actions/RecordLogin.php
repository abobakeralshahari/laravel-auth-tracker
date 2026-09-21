<?php

namespace Alshahari\AuthTracker\Actions;

use Alshahari\AuthTracker\Events\Login as LegacyLoginEvent;
use Alshahari\AuthTracker\Events\SessionRotated;
use Alshahari\AuthTracker\Events\SessionStarted;
use Alshahari\AuthTracker\Models\Login;
use Alshahari\AuthTracker\RequestContext;
use Alshahari\AuthTracker\Support\Credential;
use Alshahari\AuthTracker\TrackerManager;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Record a new login, or rotate the credential of an existing one.
 */
class RecordLogin
{
    public function __construct(
        protected TrackerManager $tracker,
        protected EnforceSessionLimit $limiter,
        protected AssessRisk $risk,
    ) {
    }

    /**
     * Create a login for the user from the credential and request context.
     */
    public function execute(Authenticatable $user, Credential $credential, RequestContext $context, ?string $guard = null): Login
    {
        $model = $this->tracker->loginModel();

        /** @var Login $login */
        $login = new $model;

        $login->fill($this->attributesFromContext($context));
        $login->fill([
            'guard' => $guard ?: $this->tracker->guardForDriver($credential->driver),
            'last_activity_at' => now(),
        ]);

        $this->applyCredential($login, $credential);

        $user->logins()->save($login);

        $this->attachDevice($context, $user);

        // May revoke the new login and throw (on_exceed = reject).
        $this->limiter->execute($user, $login);

        $this->risk->execute($user, $login);

        event(new SessionStarted($user, $login, $context));
        event(new LegacyLoginEvent($user, $context));

        return $login;
    }

    /**
     * Replace the credential of a login (token refresh): the login record
     * stays the same, only its credential changes.
     */
    public function rotate(Login $login, Credential $credential): Login
    {
        $previous = (string) $login->credentialId();

        $this->applyCredential($login, $credential);

        $login->forceFill([
            'rotations' => $login->rotations + 1,
            'last_rotated_at' => now(),
            'last_activity_at' => now(),
        ])->save();

        event(new SessionRotated($login, $previous));

        return $login;
    }

    protected function applyCredential(Login $login, Credential $credential): void
    {
        $driver = $this->tracker->driver($credential->driver);

        $login->forceFill([
            'driver' => $credential->driver,
            'credential_id' => $credential->id,
            'remember_token' => $credential->rememberToken,
        ]);

        if ($column = $driver->legacyColumn()) {
            $login->{$column} = $credential->id;
        }

        $login->expiresAt($credential->expiresAt);
    }

    protected function attributesFromContext(RequestContext $context): array
    {
        $attributes = [
            'user_agent' => $context->userAgent,
            'ip' => $context->ip,
            'login_by' => $context->loginBy,
            'login_from' => $context->loginFrom,
            'device_type' => $context->parser()->getDeviceType(),
            'device_name' => $context->parser()->getDevice(),
            'platform' => $context->parser()->getPlatform(),
            'browser' => $context->parser()->getBrowser(),
            'device_id' => $context->device?->getKey(),
        ];

        if ($ip = $context->ip()) {
            $attributes += [
                'city' => $ip->getCity(),
                'region' => $ip->getRegion(),
                'country' => $ip->getCountry(),
            ];

            if (method_exists($ip, 'getLatitude') && method_exists($ip, 'getLongitude')) {
                $attributes['latitude'] = $ip->getLatitude();
                $attributes['longitude'] = $ip->getLongitude();
            }

            if (method_exists($ip, 'getCustomData') && $ip->getCustomData()) {
                $attributes['ip_data'] = $ip->getCustomData();
            }
        }

        return $attributes;
    }

    protected function attachDevice(RequestContext $context, Authenticatable $user): void
    {
        $device = $context->device;

        if ($device && ! $device->deviceable()->is($user)) {
            $device->deviceable()->associate($user);
            $device->save();
        }
    }
}
