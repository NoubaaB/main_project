<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     * Checks if the authenticated user has the specified permission.
     *
     * Usage: middleware('check.permission:charges.create')
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        // Super admins bypass all checks
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if (!$user->hasPermission($permission)) {
            return response()->json([
                'message' => 'You do not have permission to perform this action.',
                'required_permission' => $permission,
            ], 403);
        }

        return $next($request);
    }
}
