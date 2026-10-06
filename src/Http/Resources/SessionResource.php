<?php

namespace OwaisKit\AuthTracker\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \OwaisKit\AuthTracker\Models\Login
 */
class SessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'guard' => $this->guard,
            'driver' => $this->driverName(),
            'is_current' => $this->is_current,
            'is_active' => $this->isActive(),
            'login_method' => $this->login_by,
            'login_from' => $this->login_from,
            'ip' => $this->ip,
            'location' => $this->location,
            'country' => $this->country,
            'city' => $this->city,
            'platform' => $this->platform,
            'browser' => $this->browser,
            'device_type' => $this->device_type,
            'device_name' => $this->device_name,
            'device' => new DeviceResource($this->whenLoaded('device')),
            'risk_score' => $this->risk_score,
            'risk_flags' => $this->risk_flags ?? [],
            'created_at' => $this->created_at,
            'last_activity_at' => $this->last_activity_at,
            'expires_at' => $this->expires_at,
            'revoked_at' => $this->revoked_at,
            'revoked_reason' => $this->revoked_reason,
        ];
    }
}
