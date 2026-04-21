<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // If the flag is true, block the request with a 403.
        if ($user && $user->requires_password_change) {
            return response()->json([
                'message' => 'You must change your generated password before accessing the system.',
                'requires_password_change' => true
            ], 403);
        }

        return $next($request);
    }
}