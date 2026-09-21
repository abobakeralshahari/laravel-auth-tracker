<?php

namespace Alshahari\AuthTracker;

use Closure;

/**
 * Package-level registry of runtime customizations.
 *
 * Register your callbacks in a service provider's boot() method.
 */
class AuthTracker
{
    /**
     * Resolves the current tenant identifier (or null).
     */
    protected static ?Closure $tenantResolver = null;

    /**
     * Customize how the tenant identifier is resolved for a device.
     *
     * AuthTracker::resolveTenantUsing(fn () => tenant()?->id);
     */
    public static function resolveTenantUsing(?Closure $callback): void
    {
        static::$tenantResolver = $callback;
    }

    /**
     * Resolve the current tenant identifier.
     */
    public static function resolveTenant(): ?string
    {
        if (static::$tenantResolver) {
            $tenant = call_user_func(static::$tenantResolver);

            return $tenant === null ? null : (string) $tenant;
        }

        // Backward compatibility with projects using stancl/tenancy.
        if (function_exists('tenant') && ($tenant = tenant())) {
            return (string) $tenant->getTenantKey();
        }

        return null;
    }

    /**
     * Fully qualified class name of the device model.
     */
    public static function deviceModel(): string
    {
        return config('auth_tracker.device_model', Models\Device::class);
    }

    /**
     * Fully qualified class name of the login model.
     */
    public static function loginModel(): string
    {
        return config('auth_tracker.models.login', Models\Login::class);
    }

    /**
     * Determine if the given object is tracked (uses the AuthTracking trait).
     */
    public static function isTracked(mixed $user): bool
    {
        return is_object($user)
            && in_array(Traits\AuthTracking::class, class_uses_recursive($user), true);
    }

    /**
     * Reset the registered customizations (mainly for tests).
     */
    public static function flush(): void
    {
        static::$tenantResolver = null;
    }
}
