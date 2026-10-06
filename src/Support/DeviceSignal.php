<?php

namespace OwaisKit\AuthTracker\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;

/**
 * Everything the request tells us about the client device.
 *
 * Native applications send "x-device-*" headers; web browsers are
 * described by their User-Agent. This object only reads the request,
 * it never touches the database.
 */
final class DeviceSignal
{
    public function __construct(
        public readonly ?string $udid,
        public readonly ?string $type,
        public readonly ?string $os,
        public readonly ?string $osVersion,
        public readonly ?string $manufacturer,
        public readonly ?string $model,
        public readonly ?string $browser,
        public readonly ?string $fcmToken,
        public readonly ?string $appVersion,
        public readonly ?string $appType,
        public readonly ?string $tenantId,
        public readonly ?string $userAgent,
        public readonly bool $identifiedByClient,
    ) {
    }

    public static function fromRequest(Request $request, ?string $tenantId = null): self
    {
        $agent = new Agent($request->headers->all(), $request->userAgent());
        $header = fn (string $name) => self::header($request, $name);

        if ($os = $header('os')) {
            $osVersion = $header('os-version');
        } else {
            $os = $agent->platform() ?: null;
            $osVersion = $os ? ($agent->version($os) ?: null) : null;
        }

        $udid = $header('udid') ?? self::cookieDevice($request);

        return new self(
            udid: $udid,
            type: self::deviceType($agent),
            os: $os,
            osVersion: $osVersion,
            manufacturer: $header('manufacturer')
                ?? (($agent->device() === 'WebKit' ? $agent->deviceType() : $agent->device()) ?: null),
            model: $header('model') ?? ($agent->browser() ?: null),
            browser: $agent->browser() ?: null,
            fcmToken: $header('fcm-token') ?? self::cookie($request, config('auth_tracker.device.fcm_cookies', [])),
            appVersion: $header('app-version') ?? $request->cookie('app_version'),
            appType: $header('app-type') ?? self::appType($agent),
            tenantId: $tenantId ?? $request->header(config('auth_tracker.device.tenant_header', 'country-code')),
            userAgent: Str::limit((string) $request->userAgent(), 250, ''),
            identifiedByClient: $udid !== null,
        );
    }

    /**
     * Attributes to persist on the device model.
     */
    public function toArray(): array
    {
        return [
            'udid' => $this->udid,
            'type' => $this->type,
            'os' => $this->os,
            'os_version' => $this->osVersion,
            'manufacturer' => $this->manufacturer,
            'model' => $this->model,
            'browser' => $this->browser,
            'fcm_token' => $this->fcmToken,
            'app_version' => $this->appVersion,
            'app_type' => $this->appType,
            'tenant_id' => $this->tenantId,
            'user_agent' => $this->userAgent,
        ];
    }

    /**
     * Same signal with a (server generated) identifier.
     */
    public function withUdid(string $udid): self
    {
        $attributes = get_object_vars($this);
        $attributes['udid'] = $udid;

        return new self(...$attributes);
    }

    public static function headerName(string $name): string
    {
        return config('auth_tracker.device.header_prefix', 'x-device-').$name;
    }

    private static function header(Request $request, string $name): ?string
    {
        $value = $request->header(self::headerName($name));

        return $value === null || $value === '' ? null : (string) $value;
    }

    private static function cookieDevice(Request $request): ?string
    {
        $value = $request->cookie(config('auth_tracker.device.cookie', 'device_uuid'));

        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function cookie(Request $request, array $names): ?string
    {
        foreach ($names as $name) {
            if ($value = $request->cookie($name)) {
                return $value;
            }
        }

        return null;
    }

    private static function deviceType(Agent $agent): ?string
    {
        return match (true) {
            $agent->isRobot() => 'bot',
            $agent->isTablet() => 'tablet',
            $agent->isPhone() => 'phone',
            $agent->isMobile() => 'mobile',
            $agent->isDesktop() => 'desktop',
            default => null,
        };
    }

    private static function appType(Agent $agent): string
    {
        return match (true) {
            $agent->isTablet() => 'web_tablet',
            $agent->isMobile() => 'web_mobile',
            $agent->isDesktop() => 'web_pc',
            default => 'other',
        };
    }
}
