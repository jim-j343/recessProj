<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class UpdateLastActive
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();

        // Throttle: only write if the stored value is more than 5 minutes old,
        // so we don't hit the database on every single page load
        if ($user && (
            $user->last_active_at === null ||
            $user->last_active_at->lt(now()->subMinutes(5))
        )) {
            $user->forceFill(['last_active_at' => now()])->saveQuietly();
        }

        return $next($request);
    }
}
