<?php

namespace Alshahari\AuthTracker\Http\Controllers;

use Alshahari\AuthTracker\Facades\AuthTracker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Refresh endpoint for the tokens issued by AuthTracker::issueToken().
 * Public route: the refresh token is the credential.
 */
class TokenController extends Controller
{
    public function refresh(Request $request): JsonResponse
    {
        $data = $request->validate(['refresh_token' => ['required', 'string']]);

        return response()->json(AuthTracker::refreshToken($data['refresh_token'])->toArray());
    }
}
