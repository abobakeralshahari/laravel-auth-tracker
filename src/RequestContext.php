<?php

namespace Awsan\AuthTracker;

use Awsan\AuthTracker\Actions\ResolveDevice;
use Awsan\AuthTracker\Support\DeviceSignal;
use Awsan\AuthTracker\Factories\IpProviderFactory;
use Awsan\AuthTracker\Factories\ParserFactory;
use Awsan\AuthTracker\Interfaces\IpProvider;
use Awsan\AuthTracker\Interfaces\UserAgentParser;
use Awsan\AuthTracker\Models\Device;
use Illuminate\Http\Request;

/**
 * Everything we know about the request that produced a login.
 */
class RequestContext
{
    protected UserAgentParser $parser;

    protected ?IpProvider $ipProvider = null;

    public ?Device $device = null;

    public ?string $userAgent;

    public ?string $ip;

    public ?string $loginBy;

    public ?string $loginFrom;

    public ?string $deviceUdid = null;

    public function __construct(?Request $request = null)
    {
        $request = $request ?? request();

        $this->parser = ParserFactory::build(config('auth_tracker.parser'));
        $this->ipProvider = IpProviderFactory::build(config('auth_tracker.ip_lookup.provider'));

        $this->userAgent = $request->userAgent();
        $this->ip = $request->ip();
        $this->loginBy = $request->input('login_by', 'other');

        $this->device = app(ResolveDevice::class)->execute($request);
        $this->deviceUdid = $this->device->udid;

        $appTypeHeader = DeviceSignal::headerName('app-type');
        $this->loginFrom = $request->header($appTypeHeader)
            ?: $request->input('login_from', $this->device->app_type ?? 'other');
    }

    /**
     * Get the parser used to parse the User-Agent header.
     */
    public function parser(): UserAgentParser
    {
        return $this->parser;
    }

    /**
     * Get the device of the request.
     */
    public function device(): ?Device
    {
        return $this->device;
    }

    /**
     * Get the IP lookup result.
     */
    public function ip(): ?IpProvider
    {
        if ($this->ipProvider && $this->ipProvider->getResult()) {
            return $this->ipProvider;
        }

        return null;
    }
}
