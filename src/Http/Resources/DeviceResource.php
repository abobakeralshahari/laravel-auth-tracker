<?php

namespace Alshahari\AuthTracker\Http\Resources;

use Alshahari\AuthTracker\Facades\AuthTracker;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Alshahari\AuthTracker\Models\Device
 */
class DeviceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'type' => $this->type,
            'os' => $this->os,
            'os_version' => $this->os_version,
            'manufacturer' => $this->manufacturer,
            'model' => $this->model,
            'browser' => $this->browser,
            'app_type' => $this->app_type,
            'app_version' => $this->app_version,
            'is_current' => AuthTracker::currentDevice()?->is($this->resource) ?? false,
            'is_trusted' => $this->isTrusted(),
            'is_blocked' => $this->isBlocked(),
            'trusted_until' => $this->trusted_until,
            'first_seen_at' => $this->created_at,
            'last_seen_at' => $this->last_seen_at,
            'active_sessions_count' => $this->whenCounted('logins'),
        ];
    }
}
