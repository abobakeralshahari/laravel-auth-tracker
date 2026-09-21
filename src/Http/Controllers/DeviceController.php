<?php

namespace Alshahari\AuthTracker\Http\Controllers;

use Alshahari\AuthTracker\Facades\AuthTracker;
use Alshahari\AuthTracker\Http\Resources\DeviceResource;
use Alshahari\AuthTracker\Models\Device;
use Carbon\CarbonInterval;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

/**
 * "My devices" endpoints, registered by AuthTracker::routes().
 */
class DeviceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return DeviceResource::collection(AuthTracker::devices($request->user()));
    }

    public function update(Request $request, int|string $id): DeviceResource
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);

        $device = $this->device($request, $id);

        AuthTracker::renameDevice($device, $data['name']);

        return new DeviceResource($device);
    }

    public function trust(Request $request, int|string $id): DeviceResource
    {
        $data = $request->validate(['days' => ['nullable', 'integer', 'min:1', 'max:365']]);

        $device = $this->device($request, $id);

        AuthTracker::trustDevice($device, isset($data['days']) ? CarbonInterval::days((int) $data['days']) : null);

        return new DeviceResource($device);
    }

    public function untrust(Request $request, int|string $id): DeviceResource
    {
        $device = $this->device($request, $id);

        AuthTracker::untrustDevice($device);

        return new DeviceResource($device);
    }

    /**
     * Revoke every session of the device.
     */
    public function destroy(Request $request, int|string $id): JsonResponse
    {
        return response()->json(['revoked' => AuthTracker::revokeDevice($this->device($request, $id))]);
    }

    /**
     * A device is only reachable by the users that logged in from it.
     */
    protected function device(Request $request, int|string $id): Device
    {
        $device = AuthTracker::devices($request->user())->firstWhere(fn (Device $device) => (string) $device->getKey() === (string) $id);

        abort_if(! $device, 404);

        return $device;
    }
}
