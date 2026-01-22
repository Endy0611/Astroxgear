<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|string|email|max:255|unique:users',
            'password'              => 'required|string|min:6',
            'password_confirmation' => 'sometimes|same:password',
            'firebase_uid'          => 'nullable|string|unique:users,firebase_uid',
        ]);

        $user = User::create([
            'name'         => $request->name,
            'email'        => $request->email,
            'password'     => Hash::make($request->password),
            'role'         => 'customer', // Changed to 'customer' to match enum
            'is_active'    => true,
            'firebase_uid' => $request->firebase_uid ?? null,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message'    => 'Registration successful',
            'user'       => $user,
            'token'      => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'        => 'required|email',
            'password'     => 'required',
            'firebase_uid' => 'nullable|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Check if account is active
        if (! $user->is_active) {
            return response()->json([
                'message' => 'Your account is inactive. Please contact support.',
            ], 403);
        }

        // Update firebase_uid if provided and not already set
        if ($request->firebase_uid && ! $user->firebase_uid) {
            $user->update(['firebase_uid' => $request->firebase_uid]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message'    => 'Login successful',
            'user'       => $user,
            'token'      => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Firebase Google Authentication
     */
    public function firebaseAuth(Request $request)
    {
        $request->validate([
            'uid'         => 'required|string',
            'email'       => 'required|email',
            'displayName' => 'nullable|string',
            'photoURL'    => 'nullable|string',
        ]);

        try {
            // Check if user exists by firebase_uid first, then by email
            $user = User::where('firebase_uid', $request->uid)
                ->orWhere('email', $request->email)
                ->first();

            if ($user) {
                // User exists - update Firebase info
                $user->update([
                    'firebase_uid' => $request->uid,
                    'avatar'       => $request->photoURL ?? $user->avatar,
                    'name'         => $request->displayName ?? $user->name,
                ]);

                // Check if account is active
                if (! $user->is_active) {
                    return response()->json([
                        'success' => false,
                        'error'   => 'Your account is inactive. Please contact support.',
                    ], 403);
                }
            } else {
                // Create new user from Firebase
                $username = explode('@', $request->email)[0];

                $user = User::create([
                    'name'              => $request->displayName ?? $username,
                    'email'             => $request->email,
                    'firebase_uid'      => $request->uid,
                    'avatar'            => $request->photoURL,
                    'password'          => Hash::make(Str::random(32)), // Random password for Firebase users
                    'role'              => 'customer', // Changed to 'customer' to match enum
                    'is_active'         => true,
                    'email_verified_at' => now(), // Firebase already verified email
                ]);
            }

            // Create authentication token
            $token = $user->createToken('firebase-auth')->plainTextToken;

            return response()->json([
                'success'    => true,
                'message'    => 'Authentication successful',
                'user'       => [
                    'id'     => $user->id,
                    'name'   => $user->name,
                    'email'  => $user->email,
                    'avatar' => $user->avatar,
                    'role'   => $user->role,
                ],
                'token'      => $token,
                'token_type' => 'Bearer',
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Firebase Auth Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error'   => 'Authentication failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Get current authenticated user
     */
    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }
}