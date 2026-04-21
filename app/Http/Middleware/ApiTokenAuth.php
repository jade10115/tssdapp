<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ApiTokenAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization', '');
        $token = null;

        // Expect: "Bearer <token>"
        if (preg_match('/Bearer\s+(.+)/i', $header, $m)) {
            $token = trim($m[1]);
        }

        if (!$token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user = User::where('api_token', $token)->first();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // So $request->user() works
        Auth::setUser($user);

        return $next($request);
    }
}
