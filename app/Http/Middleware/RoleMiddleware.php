<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user = Auth::user();
        
        // Use the new hasAnyRole method for cleaner code
        if ($user->hasAnyRole($roles)) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Forbidden - Insufficient permissions',
            'required_roles' => $roles,
            'user_roles' => $user->roles->pluck('name')->toArray()
        ], 403);
    }
}