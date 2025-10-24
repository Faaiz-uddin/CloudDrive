<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'status' => false,
                'message' => 'Access denied. Admin privileges are required to continue.'
            ], 403);
        }

        return $next($request);
    }
}
