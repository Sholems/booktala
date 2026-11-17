<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

class ApiAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('Authorization');

        if (!$token) {
            return response()->json(['message' => 'Authorization token required'], 401);
        }

        $token = str_replace('Bearer ', '', $token);

        $user = User::where('api_token', $token)->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        auth()->setUser($user);

        return $next($request);
    }
}