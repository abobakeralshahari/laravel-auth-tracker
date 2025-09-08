<?php

namespace Alshahari\AuthTracker\Services;

use Alshahari\AuthTracker\Models\Device;
use Alshahari\AuthTracker\Models\Login;
use Alshahari\AuthTracker\Events\LoginDetected;
use Alshahari\AuthTracker\Events\SuspiciousLoginDetected;
use Alshahari\AuthTracker\Events\DeviceRegistered;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Main Auth Tracker Service
 * 
 * This service provides comprehensive authentication tracking capabilities
 * without exposing direct endpoints. It's designed to be used as a service
 * within your application.
 */
class AuthTrackerService
{
    protected $deviceService;
    protected $notificationService;
    protected $securityService;

    public function __construct(
        DeviceService $deviceService,
        NotificationService $notificationService,
        SecurityService $securityService
    ) {
        $this->deviceService = $deviceService;
        $this->notificationService = $notificationService;
        $this->securityService = $securityService;
    }

    /**
     * Track user login
     *
     * @param Authenticatable $user
     * @param array $context
     * @param bool $remember
     * @return Login
     */
    public function trackLogin(Authenticatable $user, array $context = [], bool $remember = false): Login
    {
        try {
            // Get or create device
            $device = $this->deviceService->getOrCreateDevice($context);
            
            // Create login record
            $login = $this->createLoginRecord($user, $device, $context, $remember);
            
            // Check for suspicious activity
            if ($this->isSuspiciousLogin($user, $device, $context)) {
                event(new SuspiciousLoginDetected($user, $device, $login, $context));
            }
            
            // Dispatch login event
            event(new LoginDetected($user, $device, $login, $context));
            
            // Send notifications if configured
            $this->notificationService->sendLoginNotification($user, $device, $login);
            
            Log::info('Login tracked successfully', [
                'user_id' => $user->id,
                'device_id' => $device->id,
                'login_id' => $login->id,
                'ip' => $context['ip'] ?? null
            ]);
            
            return $login;
            
        } catch (\Exception $e) {
            Log::error('Failed to track login', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'context' => $context
            ]);
            throw $e;
        }
    }

    /**
     * Get user's active logins
     *
     * @param Authenticatable $user
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveLogins(Authenticatable $user, array $filters = [])
    {
        $query = $user->logins()
            ->where('logout_at', null)
            ->where('cleared_by_user', false)
            ->with('device');

        // Apply filters
        if (isset($filters['device_type'])) {
            $query->where('device_type', $filters['device_type']);
        }

        if (isset($filters['platform'])) {
            $query->where('platform', $filters['platform']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('updated_at', 'desc')->get();
    }

    /**
     * Get user's login history
     *
     * @param Authenticatable $user
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLoginHistory(Authenticatable $user, array $filters = [])
    {
        $query = $user->logins()
            ->where('logout_at', '!=', null)
            ->where('cleared_by_user', true)
            ->with('device');

        // Apply same filters as active logins
        if (isset($filters['device_type'])) {
            $query->where('device_type', $filters['device_type']);
        }

        if (isset($filters['platform'])) {
            $query->where('platform', $filters['platform']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('logout_at', 'desc')->get();
    }

    /**
     * Revoke specific login
     *
     * @param Authenticatable $user
     * @param int $loginId
     * @return bool
     */
    public function revokeLogin(Authenticatable $user, int $loginId): bool
    {
        $login = $user->logins()->find($loginId);
        
        if (!$login) {
            return false;
        }

        return $this->revokeLoginRecord($login);
    }

    /**
     * Revoke all logins except current
     *
     * @param Authenticatable $user
     * @return int Number of revoked logins
     */
    public function revokeOtherLogins(Authenticatable $user): int
    {
        $currentLogin = $this->getCurrentLogin($user);
        $currentLoginId = $currentLogin ? $currentLogin->id : null;

        $otherLogins = $user->logins()
            ->where('logout_at', null)
            ->where('cleared_by_user', false)
            ->when($currentLoginId, function ($query) use ($currentLoginId) {
                return $query->where('id', '!=', $currentLoginId);
            })
            ->get();

        $revokedCount = 0;
        foreach ($otherLogins as $login) {
            if ($this->revokeLoginRecord($login)) {
                $revokedCount++;
            }
        }

        return $revokedCount;
    }

    /**
     * Revoke all logins
     *
     * @param Authenticatable $user
     * @return int Number of revoked logins
     */
    public function revokeAllLogins(Authenticatable $user): int
    {
        $logins = $user->logins()
            ->where('logout_at', null)
            ->where('cleared_by_user', false)
            ->get();

        $revokedCount = 0;
        foreach ($logins as $login) {
            if ($this->revokeLoginRecord($login)) {
                $revokedCount++;
            }
        }

        return $revokedCount;
    }

    /**
     * Get current login
     *
     * @param Authenticatable $user
     * @return Login|null
     */
    public function getCurrentLogin(Authenticatable $user): ?Login
    {
        return $user->currentLogin();
    }

    /**
     * Get login statistics
     *
     * @param Authenticatable $user
     * @param array $filters
     * @return array
     */
    public function getLoginStatistics(Authenticatable $user, array $filters = []): array
    {
        $query = $user->logins();

        // Apply date filters
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $logins = $query->get();

        return [
            'total_logins' => $logins->count(),
            'active_sessions' => $logins->where('logout_at', null)->where('cleared_by_user', false)->count(),
            'unique_devices' => $logins->pluck('device_id')->unique()->count(),
            'login_by' => $logins->groupBy('login_by')->map->count(),
            'login_from' => $logins->groupBy('login_from')->map->count(),
            'devices' => $logins->groupBy('platform')->map->count(),
            'locations' => $logins->groupBy('city')->map->count(),
        ];
    }

    /**
     * Create login record
     *
     * @param Authenticatable $user
     * @param Device $device
     * @param array $context
     * @param bool $remember
     * @return Login
     */
    protected function createLoginRecord(Authenticatable $user, Device $device, array $context, bool $remember): Login
    {
        $login = new Login();
        
        $login->fill([
            'authenticatable_type' => get_class($user),
            'authenticatable_id' => $user->id,
            'device_id' => $device->id,
            'user_agent' => $context['user_agent'] ?? request()->userAgent(),
            'ip' => $context['ip'] ?? request()->ip(),
            'device_type' => $context['device_type'] ?? 'unknown',
            'device' => $context['device'] ?? 'unknown',
            'platform' => $context['platform'] ?? 'unknown',
            'browser' => $context['browser'] ?? 'unknown',
            'city' => $context['city'] ?? null,
            'region' => $context['region'] ?? null,
            'country' => $context['country'] ?? null,
            'login_by' => $context['login_by'] ?? 'web',
            'login_from' => $context['login_from'] ?? 'web_pc',
            'session_id' => $context['session_id'] ?? session()->getId(),
            'remember_token' => $remember ? Str::random(60) : null,
            'oauth_access_token_id' => $context['oauth_access_token_id'] ?? null,
            'personal_access_token_id' => $context['personal_access_token_id'] ?? null,
        ]);

        // Set expiration
        if ($remember) {
            $login->expiresAt(Carbon::now()->addDays(config('auth_tracker.remember_lifetime', 365)));
        } else {
            $login->expiresAt(Carbon::now()->addMinutes(config('session.lifetime', 120)));
        }

        $login->save();

        return $login;
    }

    /**
     * Revoke login record
     *
     * @param Login $login
     * @return bool
     */
    protected function revokeLoginRecord(Login $login): bool
    {
        try {
            // Revoke based on type
            if ($login->session_id) {
                $this->revokeSession($login->session_id);
            } elseif ($login->oauth_access_token_id) {
                $this->revokePassportToken($login->oauth_access_token_id);
            } elseif ($login->personal_access_token_id) {
                $this->revokeSanctumToken($login->personal_access_token_id);
            }

            // Mark as revoked
            $login->update([
                'cleared_by_user' => true,
                'logout_at' => Carbon::now()
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to revoke login', [
                'login_id' => $login->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check if login is suspicious
     *
     * @param Authenticatable $user
     * @param Device $device
     * @param array $context
     * @return bool
     */
    protected function isSuspiciousLogin(Authenticatable $user, Device $device, array $context): bool
    {
        // Check for new device
        $isNewDevice = !$user->devices()->where('id', $device->id)->exists();
        
        // Check for different location
        $lastLogin = $user->logins()->latest()->first();
        $isDifferentLocation = $lastLogin && 
            $lastLogin->country !== ($context['country'] ?? null);
        
        // Check for unusual time
        $currentHour = now()->hour;
        $isUnusualTime = $currentHour < 6 || $currentHour > 22;
        
        return $isNewDevice || $isDifferentLocation || $isUnusualTime;
    }

    /**
     * Revoke session
     *
     * @param string $sessionId
     * @return void
     */
    protected function revokeSession(string $sessionId): void
    {
        // Implementation depends on session driver
        if (config('session.driver') === 'database') {
            \DB::table('sessions')->where('id', $sessionId)->delete();
        }
    }

    /**
     * Revoke Passport token
     *
     * @param string $tokenId
     * @return void
     */
    protected function revokePassportToken(string $tokenId): void
    {
        \DB::table('oauth_access_tokens')->where('id', $tokenId)->update(['revoked' => true]);
    }

    /**
     * Revoke Sanctum token
     *
     * @param int $tokenId
     * @return void
     */
    protected function revokeSanctumToken(int $tokenId): void
    {
        \DB::table('personal_access_tokens')->where('id', $tokenId)->delete();
    }
}
