<?php

namespace OwaisKit\AuthTracker\Macros;

use OwaisKit\AuthTracker\Facades\AuthTracker;

class RouteMacros
{
    /**
     * Route::authTracker('security') — kept for backward compatibility,
     * registers the package API routes under the given prefix.
     *
     * @return \Closure
     */
    public function authTracker()
    {
        return function (string $prefix, array|string $middleware = ['auth']) {
            AuthTracker::routes($prefix, $middleware);
        };
    }
}
