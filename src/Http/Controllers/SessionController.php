<?php

namespace Alshahari\AuthTracker\Http\Controllers;

use Alshahari\AuthTracker\Actions\RevokeLogin;
use Alshahari\AuthTracker\Facades\AuthTracker;
use Alshahari\AuthTracker\Http\Resources\SessionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

/**
 * "My sessions" endpoints, registered by AuthTracker::routes().
 */
class SessionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return SessionResource::collection(AuthTracker::active($request->user(), $request->query('guard')));
    }

    public function history(Request $request): AnonymousResourceCollection
    {
        return SessionResource::collection(AuthTracker::history($request->user(), (int) $request->query('days', 90)));
    }

    public function current(Request $request): SessionResource|JsonResponse
    {
        $login = AuthTracker::current($request->user());

        return $login ? new SessionResource($login->load('device')) : response()->json(null, 204);
    }

    public function destroy(Request $request, int|string $id): JsonResponse
    {
        $login = AuthTracker::find($request->user(), $id);

        abort_if(! $login, 404);

        return response()->json(['revoked' => AuthTracker::revoke($login, RevokeLogin::REASON_USER)]);
    }

    public function destroyOthers(Request $request): JsonResponse
    {
        return response()->json(['revoked' => AuthTracker::revokeOthers($request->user())]);
    }

    public function destroyAll(Request $request): JsonResponse
    {
        return response()->json(['revoked' => AuthTracker::revokeAll($request->user())]);
    }
}
