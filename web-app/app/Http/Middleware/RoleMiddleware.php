<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role): mixed
    {
        if (!$request->user() || $request->user()->system_role !== $role) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
