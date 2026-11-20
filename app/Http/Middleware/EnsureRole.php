<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * role:staff  -> admin or staff
 * role:admin  -> admin only
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role = 'staff'): Response
    {
        $user = $request->user();

        $allowed = $user && $user->is_active && match ($role) {
            'admin' => $user->isAdmin(),
            default => $user->isStaff(),
        };

        if (! $allowed) {
            return response()->json(['message' => 'You do not have permission to do this.'], 403);
        }

        return $next($request);
    }
}
