<?php

namespace Alshahari\AuthTracker\Http;

use Alshahari\AuthTracker\Http\Controllers\DeviceController;
use Alshahari\AuthTracker\Http\Controllers\SessionController;
use Alshahari\AuthTracker\Http\Controllers\TokenController;
use Illuminate\Contracts\Routing\Registrar as Router;

/**
 * Registers the package API routes.
 *
 *   AuthTracker::routes(prefix: 'account/security', middleware: ['auth:sanctum']);
 *
 *   GET    sessions                sessions.index
 *   GET    sessions/history        sessions.history
 *   GET    sessions/current        sessions.current
 *   DELETE sessions/{id}           sessions.destroy
 *   DELETE sessions                sessions.destroy-others
 *   DELETE sessions/all            sessions.destroy-all
 *   GET    devices                 devices.index
 *   PATCH  devices/{id}            devices.update       (rename)
 *   POST   devices/{id}/trust      devices.trust
 *   DELETE devices/{id}/trust      devices.untrust
 *   DELETE devices/{id}            devices.destroy      (revoke its sessions)
 *   POST   token/refresh           token.refresh        (public)
 */
class RouteRegistrar
{
    public function __construct(protected Router $router)
    {
    }

    /**
     * @param  list<string>|null  $only    Route names to register (e.g. ['sessions.index']).
     * @param  list<string>|null  $except  Route names to skip.
     */
    public function register(
        string $prefix = 'auth-tracker',
        array|string $middleware = ['auth'],
        ?array $only = null,
        ?array $except = null,
        string $name = 'auth-tracker.',
    ): void {
        $routes = [
            'sessions.index' => ['get', 'sessions', [SessionController::class, 'index']],
            'sessions.history' => ['get', 'sessions/history', [SessionController::class, 'history']],
            'sessions.current' => ['get', 'sessions/current', [SessionController::class, 'current']],
            'sessions.destroy-all' => ['delete', 'sessions/all', [SessionController::class, 'destroyAll']],
            'sessions.destroy-others' => ['delete', 'sessions', [SessionController::class, 'destroyOthers']],
            'sessions.destroy' => ['delete', 'sessions/{id}', [SessionController::class, 'destroy']],
            'devices.index' => ['get', 'devices', [DeviceController::class, 'index']],
            'devices.update' => ['patch', 'devices/{id}', [DeviceController::class, 'update']],
            'devices.trust' => ['post', 'devices/{id}/trust', [DeviceController::class, 'trust']],
            'devices.untrust' => ['delete', 'devices/{id}/trust', [DeviceController::class, 'untrust']],
            'devices.destroy' => ['delete', 'devices/{id}', [DeviceController::class, 'destroy']],
        ];

        $public = [
            'token.refresh' => ['post', 'token/refresh', [TokenController::class, 'refresh']],
        ];

        $wanted = fn (string $route) => ($only === null || in_array($route, $only, true))
            && ($except === null || ! in_array($route, $except, true));

        $this->router->group(['prefix' => $prefix, 'as' => $name], function () use ($routes, $public, $middleware, $wanted) {
            $this->router->group(['middleware' => $middleware], function () use ($routes, $wanted) {
                foreach ($routes as $route => [$method, $uri, $action]) {
                    if ($wanted($route)) {
                        $this->router->{$method}($uri, $action)->name($route);
                    }
                }
            });

            foreach ($public as $route => [$method, $uri, $action]) {
                if ($wanted($route) && class_exists(\Laravel\Sanctum\Sanctum::class)) {
                    $this->router->{$method}($uri, $action)->name($route)->middleware('throttle:10,1');
                }
            }
        });
    }
}
