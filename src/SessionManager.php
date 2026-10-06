<?php

namespace Awsan\AuthTracker;

use Awsan\AuthTracker\Actions\ResolveDevice;
use Awsan\AuthTracker\Actions\RevokeLogin;
use Awsan\AuthTracker\Drivers\SessionDriver;
use Awsan\AuthTracker\Events\DeviceTrusted;
use Awsan\AuthTracker\Models\Device;
use Awsan\AuthTracker\Models\Login;
use Carbon\CarbonInterval;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;

/**
 * All the operations on tracked logins and devices. Every presentation
 * layer (trait, facade, controllers, Filament...) goes through here.
 */
class SessionManager
{
    /**
     * Current login memoized per user for the lifetime of the request.
     *
     * @var array<string, Login|null>
     */
    protected array $current = [];

    public function __construct(
        protected TrackerManager $tracker,
        protected RevokeLogin $revoker,
    ) {
    }

    // ------------------------------------------------------------------
    //  Queries
    // ------------------------------------------------------------------

    /**
     * Active logins of the user (not revoked, not expired), most recent first.
     */
    public function active(Authenticatable $user, ?string $guard = null): Collection
    {
        return $this->activeQuery($user)
            ->when($guard, fn (Builder $query) => $query->where('guard', $guard))
            ->with('device')
            ->orderByDesc('last_activity_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Revoked or expired logins of the last given days.
     */
    public function history(Authenticatable $user, int $days = 90): Collection
    {
        return $this->logins($user)
            ->withExpired()
            ->where(fn (Builder $query) => $query
                ->whereNotNull('revoked_at')
                ->orWhere('expires_at', '<=', now()))
            ->where('created_at', '>=', now()->subDays($days))
            ->with('device')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * The login of the current request.
     */
    public function current(?Authenticatable $user = null): ?Login
    {
        $user = $user ?? Auth::user();

        if (! $user || ! $this->tracker->isTracked($user)) {
            return null;
        }

        $key = $user->getMorphClass().':'.$user->getAuthIdentifier();

        if (array_key_exists($key, $this->current)) {
            return $this->current[$key];
        }

        return $this->current[$key] = $this->resolveCurrent($user);
    }

    /**
     * Forget the memoized current login (after a logout, for instance).
     */
    public function forgetCurrent(): void
    {
        $this->current = [];
    }

    protected function resolveCurrent(Authenticatable $user): ?Login
    {
        foreach ($this->tracker->driverNames() as $name) {
            $driver = $this->tracker->driver($name);

            if ($driver instanceof SessionDriver && ($id = $driver->currentLoginId())) {
                return $this->activeQuery($user)->find($id);
            }

            if ($credentialId = $driver->currentCredentialId($user)) {
                return $this->activeQuery($user)
                    ->where('driver', $name)
                    ->where('credential_id', $credentialId)
                    ->first();
            }
        }

        return null;
    }

    public function find(Authenticatable $user, int|string $id): ?Login
    {
        return $this->logins($user)->withExpired()->find($id);
    }

    // ------------------------------------------------------------------
    //  Revocation
    // ------------------------------------------------------------------

    public function revoke(Login $login, string $reason = RevokeLogin::REASON_USER): bool
    {
        $this->forgetCurrent();

        return $this->revoker->execute($login, $reason);
    }

    /**
     * Revoke every active login of the user except the current one.
     *
     * @return int  Number of revoked logins.
     */
    public function revokeOthers(Authenticatable $user, string $reason = RevokeLogin::REASON_USER): int
    {
        $current = $this->current($user);

        $logins = $this->activeQuery($user)
            ->when($current, fn (Builder $query) => $query->whereKeyNot($current->getKey()))
            ->get();

        return $this->revoker->many($logins, $reason);
    }

    /**
     * Revoke every active login of the user, including the current one.
     */
    public function revokeAll(Authenticatable $user, string $reason = RevokeLogin::REASON_USER_ALL): int
    {
        return $this->revoker->many($this->activeQuery($user)->get(), $reason);
    }

    /**
     * Revoke every active login made from the device.
     */
    public function revokeDevice(Device $device, string $reason = RevokeLogin::REASON_USER): int
    {
        $logins = $device->logins()
            ->whereNull('revoked_at')
            ->get();

        return $this->revoker->many($logins, $reason);
    }

    // ------------------------------------------------------------------
    //  Devices
    // ------------------------------------------------------------------

    /**
     * Devices that were used by the user, most recently seen first.
     */
    public function devices(Authenticatable $user): Collection
    {
        $loginModel = $this->tracker->loginModel();

        return $this->tracker->deviceModel()::query()
            ->whereIn('id', (new $loginModel)->newQueryWithoutScopes()
                ->select('device_id')
                ->where('authenticatable_type', $user->getMorphClass())
                ->where('authenticatable_id', $user->getAuthIdentifier())
                ->whereNotNull('device_id'))
            ->orderByDesc('last_seen_at')
            ->get();
    }

    /**
     * The device of the current request, if it was resolved.
     */
    public function currentDevice(): ?Device
    {
        return ResolveDevice::current();
    }

    public function renameDevice(Device $device, string $name): void
    {
        $device->forceFill(['name' => $name])->save();
    }

    /**
     * Trust the device (skip the second factor, for instance) for a
     * period, or forever when no period is given.
     */
    public function trustDevice(Device $device, ?CarbonInterval $for = null): void
    {
        $device->forceFill([
            'trusted_at' => now(),
            'trusted_until' => $for ? now()->add($for) : null,
        ])->save();

        event(new DeviceTrusted($device));
    }

    public function untrustDevice(Device $device): void
    {
        $device->forceFill(['trusted_at' => null, 'trusted_until' => null])->save();
    }

    /**
     * Is the device of the current request trusted by the given user?
     * A device is only trusted for the users that logged in from it.
     */
    public function isTrustedDevice(?Device $device = null, ?Authenticatable $user = null): bool
    {
        $device = $device ?? $this->currentDevice();
        $user = $user ?? Auth::user();

        if (! $device || ! $device->isTrusted()) {
            return false;
        }

        return ! $user || $device->logins()
            ->withExpired()
            ->where('authenticatable_type', $user->getMorphClass())
            ->where('authenticatable_id', $user->getAuthIdentifier())
            ->exists();
    }

    /**
     * Block the device: its logins are revoked and it can no longer
     * authenticate (see the EnsureDeviceNotBlocked middleware).
     */
    public function blockDevice(Device $device, string $reason = RevokeLogin::REASON_SECURITY): int
    {
        $device->forceFill(['blocked_at' => now(), 'trusted_at' => null, 'trusted_until' => null])->save();

        return $this->revokeDevice($device, $reason);
    }

    public function unblockDevice(Device $device): void
    {
        $device->forceFill(['blocked_at' => null])->save();
    }

    /**
     * Revoke the device logins and delete the device.
     *
     * @return int  Number of revoked logins.
     */
    public function forgetDevice(Device $device): int
    {
        $revoked = $this->revokeDevice($device, RevokeLogin::REASON_USER);

        $device->delete();

        return $revoked;
    }

    // ------------------------------------------------------------------
    //  Helpers
    // ------------------------------------------------------------------

    protected function logins(Authenticatable $user): MorphMany
    {
        return $user->logins();
    }

    protected function activeQuery(Authenticatable $user): MorphMany
    {
        return $this->logins($user)->whereNull('revoked_at');
    }
}
