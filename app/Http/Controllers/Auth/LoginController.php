<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\UserResource;

class LoginController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): array
    {
        $request->authenticate();

        // $request->session()->regenerate();

        $user = $request->user();
        $token = $user->createToken('main')->plainTextToken;

        // return response()->noContent();
        return [
            'message' => 'Login successful',
            'token' => $token,
            'user' => new UserResource($user),
            'requires_password_change' => $user->requires_password_change
        ];
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): Response
    {
        // Auth::guard('web')->logout();

        // $request->session()->invalidate();

        // $request->session()->regenerateToken();

        $user = $request->user();
        $user->currentAccessToken()->delete();

        return response()->noContent();
    }
}
