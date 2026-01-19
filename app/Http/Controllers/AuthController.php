<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only(['email', 'password']);
        $remember = (bool) ($request->validated()['remember'] ?? false);

        if (! Auth::guard('web')->attempt($credentials, $remember)) {
            return response()->json(['error' => 'unauthorized'], 401);
        }

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        $user = Auth::guard('web')->user();
        $forcePasswordReset = $this->shouldForcePasswordReset($user);

        return response()->json([
            'status' => 'success',
            'force_password_reset' => $forcePasswordReset,
        ]);
    }

    public function me(): JsonResponse
    {
        if (! Auth::guard('web')->check()) {
            return response()->json(['error' => 'unauthorized'], 401);
        }

        $user = Auth::guard('web')->user();
        $payload = $user ? $user->toArray() : [];
        $payload['force_password_reset'] = $this->shouldForcePasswordReset($user);

        return response()->json($payload);
    }

    public function logout(Request $request): JsonResponse
    {
        if (! Auth::guard('web')->check()) {
            return response()->json(['error' => 'unauthorized'], 401);
        }

        Auth::guard('web')->logout();
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $user = new User;
        $user->username = $payload['username'];
        $user->email = $payload['email'];
        $user->name = $payload['displayName'];
        $user->role = 'editor';
        $user->password = Hash::make($payload['password']);
        $user->save();

        return response()->json(['status' => 'success'], 200);
    }

    /**
     * Determine if the user is still using the seeded admin credentials.
     */
    protected function shouldForcePasswordReset(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        // Seeded hashes for default credentials
        $seededAdminHash = '$2y$12$Lar5T5y8docuOFsdx98FRevUlRMZRP/40zpowaLJHz2ZtN9b/pww2'; // "admin"

        return $user->email === 'admin@example.com'
            && $user->password === $seededAdminHash;
    }
}
