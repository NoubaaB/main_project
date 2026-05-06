<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    /**
     * Handle an incoming request.
     * Super admins and users with 'users.manage' permission can pass.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }

        // Super admins always pass
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Users with users.manage permission can also access
        if ($user->hasPermission('users.manage')) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Unauthorized. Admin access required.',
        ], 403);
    }
}
