<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsNotBlacklisted
{
    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->user() && $request->user()->status === 'blacklisted') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Your account is currently blacklisted. Contact an administrator.']);
        }

        return $next($request);
    }
}
