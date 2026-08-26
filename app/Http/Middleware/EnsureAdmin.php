<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate the /api/v4/admin/* route group to admin-panel users only.
 * The group is otherwise auth:v4api (any authenticated user, incl. mobile
 * end-users), which let non-admins reach admin endpoints. Admin panel users
 * carry role 'admin' or 'super-admin'.
 */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('v4api')->user();
        if (! $user || ! in_array($user->role, ['admin', 'super-admin'], true)) {
            return response()->json(['success' => false, 'message' => 'Forbidden. Admin access required.'], 403);
        }

        return $next($request);
    }
}
