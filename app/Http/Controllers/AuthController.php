<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $fields = $request->validated();

        try {
            // Only name, email and password are ever read from the request,
            // so nobody can register themselves as an admin.
            $user = User::create([
                'name' => $fields['name'],
                'email' => $fields['email'],
                'password' => bcrypt($fields['password']),
            ]);

            $token = $user->createToken('riderbuddytoken')->plainTextToken;

            return response()->json([
                // refresh() loads the database defaults (role, is_active, ...) so the shape matches login
                'user' => $user->refresh(),
                'token' => $token,
            ], 201);

        } catch (Exception $e) {
            Log::error("Register Error: " . $e->getMessage());
            return response()->json([
                'message' => 'Registration failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    public function login(LoginRequest $request)
    {
        $fields = $request->validated();

        $user = User::where('email', $fields['email'])->first();

        // Same message for "no such email" and "wrong password"
        if (!$user || !Hash::check($fields['password'], $user->password)) {
            return response()->json(['message' => 'Invalid email or password'], 401);
        }

        // Checked AFTER the password, so banned status is never revealed to someone who doesn't know it
        if ($user->is_banned) {
            return response()->json(['message' => 'Your account has been banned.'], 403);
        }

        $token = $user->createToken('riderbuddytoken')->plainTextToken;

        return response()->json(['user' => $user, 'token' => $token], 200);
    }

    // The route already pointed here, but this method did not exist
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully'], 200);
    }
}