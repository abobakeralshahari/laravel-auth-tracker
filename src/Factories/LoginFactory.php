<?php

namespace Alshahari\AuthTracker\Factories;

use Alshahari\AuthTracker\AuthTracker;
use Alshahari\AuthTracker\Events\PersonalAccessTokenCreated;
use Alshahari\AuthTracker\Models\Login;
use Alshahari\AuthTracker\RequestContext;
use Illuminate\Auth\Events\Login as LoginEvent;
use Laravel\Passport\Events\AccessTokenCreated;

class LoginFactory
{
    /**
     * Build a new (unsaved) Login from an auth event and its request context.
     *
     * @param  LoginEvent|AccessTokenCreated|PersonalAccessTokenCreated  $event
     */
    public static function build($event, RequestContext $context): Login
    {
        $model = AuthTracker::loginModel();

        /** @var Login $login */
        $login = new $model;

        $login->fill([
            'user_agent' => $context->userAgent,
            'ip' => $context->ip,
            'login_by' => $context->loginBy,
            'login_from' => $context->loginFrom,
            'device_type' => $context->parser()->getDeviceType(),
            'device_name' => $context->parser()->getDevice(),
            'platform' => $context->parser()->getPlatform(),
            'browser' => $context->parser()->getBrowser(),
            'device_id' => $context->device?->getKey(),
        ]);

        if ($ip = $context->ip()) {
            $login->fill([
                'city' => $ip->getCity(),
                'region' => $ip->getRegion(),
                'country' => $ip->getCountry(),
            ]);

            if (method_exists($ip, 'getCustomData') && $ip->getCustomData()) {
                $login->ip_data = $ip->getCustomData();
            }
        }

        if ($event instanceof AccessTokenCreated) {
            $login->oauth_access_token_id = $event->tokenId;
        } elseif ($event instanceof PersonalAccessTokenCreated) {
            $login->personal_access_token_id = $event->personalAccessToken->getKey();
        } else {
            $login->fill([
                'session_id' => session()->getId(),
                'remember_token' => $event->remember ? $event->user->getRememberToken() : null,
            ]);
        }

        return $login;
    }
}
