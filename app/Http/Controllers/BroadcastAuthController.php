<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class BroadcastAuthController extends Controller
{
    /**
     * Authenticate the request for channel access.
     */
    public function authenticate(Request $request)
    {
        // 1. Validate required inputs
        if (!$request->filled('channel_name') || !$request->filled('socket_id')) {
            return response()->json([
                'message' => 'The socket_id and channel_name fields are required.',
            ], 422);
        }

        // 2. Validate user authentication
        if (!$request->user()) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        try {
            return Broadcast::auth($request);
        } catch (AccessDeniedHttpException $e) {
            return response()->json([
                'message' => 'Unauthorized channel access.',
            ], 403);
        } catch (\Throwable $e) {
            Log::error('Broadcasting authentication error: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'channel_name' => $request->input('channel_name'),
                'socket_id' => $request->input('socket_id'),
                'exception' => get_class($e),
                'file' => $e->getFile() . ':' . $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Broadcasting authentication error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Authenticate the user for user authentication.
     */
    public function authenticateUser(Request $request)
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        try {
            return Broadcast::resolveAuthenticatedUser($request)
                ?? throw new AccessDeniedHttpException;
        } catch (AccessDeniedHttpException $e) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        } catch (\Throwable $e) {
            Log::error('Broadcasting user auth error: ' . $e->getMessage());
            return response()->json(['message' => 'Broadcasting user auth error: ' . $e->getMessage()], 500);
        }
    }
}
