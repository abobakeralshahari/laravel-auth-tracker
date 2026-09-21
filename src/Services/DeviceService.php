<?php

namespace Alshahari\AuthTracker\Services;

use Alshahari\AuthTracker\AuthTracker;
use Alshahari\AuthTracker\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;

/**
 * Collects the device attributes from the request (headers sent by native
 * apps, cookies, or the User-Agent as a fallback) and persists the device.
 */
class DeviceService
{
    protected Request $request;

    protected Agent $agent;

    protected ?string $deviceUdid = null;

    /**
     * Collected device attributes.
     */
    protected array $attributes = [
        'udid' => null,
        'type' => null,
        'os' => null,
        'os_version' => null,
        'manufacturer' => null,
        'model' => null,
        'browser' => null,
        'fcm_token' => null,
        'app_version' => null,
        'app_type' => null,
        'tenant_id' => null,
        'user_agent' => null,
    ];

    public function __construct(?Request $request = null)
    {
        $this->request = $request ?? request();
        $this->agent = new Agent($this->request->headers->all(), $this->request->userAgent());
        $this->setAgents();
    }

    /**
     * Collect the device attributes from the request.
     */
    public function setAgents(): void
    {
        $agent = $this->agent;

        $this->attributes['user_agent'] = Str::limit((string) $this->request->userAgent(), 250, '');
        $this->attributes['type'] = $this->detectDeviceType();

        $this->attributes['manufacturer'] = $this->header('manufacturer')
            ?? ($agent->device() === 'WebKit' ? $agent->deviceType() : $agent->device()) ?: null;

        $this->attributes['model'] = $this->header('model') ?? ($agent->browser() ?: null);
        $this->attributes['browser'] = $agent->browser() ?: null;

        if ($os = $this->header('os')) {
            $this->attributes['os'] = $os;
            $this->attributes['os_version'] = $this->header('os-version');
        } elseif ($platform = $agent->platform()) {
            $this->attributes['os'] = $platform;
            $this->attributes['os_version'] = $agent->version($platform) ?: null;
        }

        $this->deviceUdid = $this->resolveUdid();
        $this->attributes['udid'] = $this->deviceUdid;

        $this->attributes['fcm_token'] = $this->header('fcm-token') ?? $this->cookieValue(config('auth_tracker.device.fcm_cookies', []));
        $this->attributes['app_type'] = $this->header('app-type') ?? $this->detectAppType();
        $this->attributes['app_version'] = $this->header('app-version') ?? $this->request->cookie('app_version');
        $this->attributes['tenant_id'] = AuthTracker::resolveTenant()
            ?? $this->request->header(config('auth_tracker.device.tenant_header', 'country-code'));
    }

    /**
     * Fill the request headers with the collected attributes so that the
     * rest of the request pipeline sees a consistent set of device headers.
     */
    public function setHeader(): void
    {
        $map = [
            'manufacturer' => 'manufacturer',
            'model' => 'model',
            'os' => 'os',
            'os-version' => 'os_version',
            'udid' => 'udid',
            'fcm-token' => 'fcm_token',
            'app-version' => 'app_version',
            'app-type' => 'app_type',
        ];

        foreach ($map as $header => $attribute) {
            if (! $this->request->hasHeader($this->headerName($header)) && $this->attributes[$attribute] !== null) {
                $this->request->headers->set($this->headerName($header), $this->attributes[$attribute]);
            }
        }
    }

    public function getAgents(): array
    {
        return $this->attributes;
    }

    public function setAgentOne(string $key, mixed $value): bool
    {
        if (! array_key_exists($key, $this->attributes)) {
            return false;
        }

        $this->attributes[$key] = $value;

        return true;
    }

    public function getAgentOne(string $key): mixed
    {
        return $this->attributes[$key] ?? false;
    }

    /**
     * Generate a cryptographically secure device identifier.
     */
    public function generateDeviceUdid(): string
    {
        return Str::random(64);
    }

    public function getDeviceId(): ?string
    {
        return $this->deviceUdid;
    }

    /**
     * Find a device by its identifier.
     */
    public function hasDeviceId(?string $udid): ?Device
    {
        if (! $udid) {
            return null;
        }

        return AuthTracker::deviceModel()::query()->where('udid', $udid)->first();
    }

    /**
     * Persist the device (create or merge into the existing one).
     */
    public function saveDevice(): Device
    {
        $model = AuthTracker::deviceModel();

        $device = $model::query()->firstOrNew(['udid' => $this->deviceUdid]);

        $device->mergeAttributes($this->attributes + ['last_seen_at' => now()]);

        return $device;
    }

    /**
     * Determine the device identifier: header, then cookie, then a new one.
     */
    protected function resolveUdid(): string
    {
        if ($udid = $this->header('udid')) {
            return $udid;
        }

        $cookie = $this->request->cookie(config('auth_tracker.device.cookie', 'device_uuid'));

        if ($cookie && $this->hasDeviceId($cookie)) {
            return $cookie;
        }

        return $this->generateDeviceUdid();
    }

    protected function detectDeviceType(): ?string
    {
        return match (true) {
            $this->agent->isRobot() => 'bot',
            $this->agent->isTablet() => 'tablet',
            $this->agent->isPhone() => 'phone',
            $this->agent->isMobile() => 'mobile',
            $this->agent->isDesktop() => 'desktop',
            default => null,
        };
    }

    protected function detectAppType(): string
    {
        return match (true) {
            $this->agent->isTablet() => 'web_tablet',
            $this->agent->isMobile() => 'web_mobile',
            $this->agent->isDesktop() => 'web_pc',
            default => 'other',
        };
    }

    /**
     * Read a device header (e.g. "x-device-os").
     */
    protected function header(string $name): ?string
    {
        $value = $this->request->header($this->headerName($name));

        return $value === null || $value === '' ? null : (string) $value;
    }

    protected function headerName(string $name): string
    {
        return config('auth_tracker.device.header_prefix', 'x-device-').$name;
    }

    /**
     * First non-empty cookie among the given names.
     */
    protected function cookieValue(array $names): ?string
    {
        foreach ($names as $name) {
            if ($value = $this->request->cookie($name)) {
                return $value;
            }
        }

        return null;
    }
}
