<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

/**
 * Token-based auth for the ACES desktop client (JavaFX).
 *
 * Mirrors the web Breeze auth flow but returns JSON + a Sanctum personal
 * access token instead of establishing a session. The desktop stores the
 * token and sends it as `Authorization: Bearer <token>` on later calls.
 */
class AuthController extends Controller
{
    /**
     * Register a new account and return an API token.
     */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username'       => ['required', 'string', 'max:80', 'unique:users,username'],
            'email'          => ['required', 'string', 'lowercase', 'email', 'max:150', 'unique:users,email'],
            'role'           => ['required', 'in:student,lecturer,system_admin'],
            'password'       => ['required', 'confirmed', Rules\Password::defaults()],
            'rules_accepted' => ['accepted'],
        ]);

        $user = User::create([
            'username'        => $data['username'],
            'email'           => $data['email'],
            'password_hash'   => Hash::make($data['password']),
            'system_role'     => $data['role'],
            'status'          => 'active',
            'agreed_to_rules' => true,
            'last_active_at'  => now(),
        ]);

        $token = $user->createToken($this->deviceName($request))->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $user,
        ], 201);
    }

    /**
     * Authenticate with email + password and return an API token.
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password_hash)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (in_array($user->status, ['blacklisted', 'suspended'], true)) {
            return response()->json([
                'message' => 'Your account is '.$user->status.'.',
            ], 403);
        }

        $user->forceFill(['last_active_at' => now()])->save();

        $token = $user->createToken($this->deviceName($request))->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $user,
        ]);
    }

    /**
     * Return the currently authenticated user (token check).
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()]);
    }

    /**
     * Revoke the token that made this request.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    private function deviceName(Request $request): string
    {
        return $request->input('device_name', 'ACES Desktop');
    }
}
