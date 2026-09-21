<?php

namespace Alshahari\AuthTracker\Services;

use Alshahari\AuthTracker\Actions\ResolveDevice;
use Alshahari\AuthTracker\Facades\AuthTracker;
use Alshahari\AuthTracker\Models\Device;
use Alshahari\AuthTracker\Support\DeviceSignal;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * 1.x compatibility layer over DeviceSignal / ResolveDevice.
 *
 * @deprecated Use AuthTracker::deviceSignal($request) and the ResolveDevice action.
 */
class DeviceService
{
    protected Request $request;

    protected DeviceSignal $signal;

    public function __construct(?Request $request = null)
    {
        $this->request = $request ?? request();
        $this->signal = AuthTracker::deviceSignal($this->request);

        if (! $this->signal->udid) {
            $this->signal = $this->signal->withUdid($this->generateDeviceUdid());
        }
    }

    public function setAgents(): void
    {
        // Attributes are collected in the constructor.
    }

    public function setHeader(): void
    {
        foreach ($this->signal->toArray() as $attribute => $value) {
            $header = DeviceSignal::headerName(str_replace('_', '-', $attribute));

            if ($value !== null && ! $this->request->hasHeader($header)) {
                $this->request->headers->set($header, $value);
            }
        }
    }

    public function getAgents(): array
    {
        return $this->signal->toArray();
    }

    public function getAgentOne(string $key): mixed
    {
        return $this->signal->toArray()[$key] ?? false;
    }

    public function generateDeviceUdid(): string
    {
        return Str::random(64);
    }

    public function getDeviceId(): ?string
    {
        return $this->signal->udid;
    }

    public function hasDeviceId(?string $udid): ?Device
    {
        return $udid ? AuthTracker::deviceModel()::query()->where('udid', $udid)->first() : null;
    }

    public function saveDevice(): Device
    {
        return app(ResolveDevice::class)->execute($this->request);
    }
}
