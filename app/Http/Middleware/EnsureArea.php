<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * area:orders -> admins, and staff whose account allows the Orders area (Settings › Staff & Roles).
 */
class EnsureArea
{
    public function handle(Request $request, Closure $next, string $area): Response
    {
        if (! $request->user()?->canAccess($area)) {
            return response()->json(['message' => 'Your account does not have access to this part of the admin. Ask an admin to allow it.'], 403);
        }

        return $next($request);
    }
}
