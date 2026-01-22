<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FirebaseAuthController extends Controller
{
    public function authenticate(Request $request)
    {
        try {
            // Validate input
            $request->validate([
                'email' => 'required|email',
                'uid' => 'required|string',
            ]);

            $email = $request->input('email');
            $uid = $request->input('uid');
            $displayName = $request->input('displayName', '');
            $photoURL = $request->input('photoURL', '');

            \Log::info('Firebase auth request:', compact('email', 'uid', 'displayName'));

            // Check if user exists by email
            $user = DB::table('users')->where('email', $email)->first();

            if ($user) {
                // User exists - update their info
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'firebase_uid' => $uid,
                        'profile_picture' => $photoURL,
                        'full_name' => $displayName,
                        'last_login' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);

                \Log::info('User updated:', ['user_id' => $user->id]);

                return response()->json([
                    'success' => true,
                    'user_id' => $user->id,
                    'role' => $user->role ?? 'customer',
                    'message' => 'User logged in successfully'
                ]);
            } else {
                // Create new user
                $username = explode('@', $email)[0];
                
                $userId = DB::table('users')->insertGetId([
                    'username' => $username,
                    'email' => $email,
                    'firebase_uid' => $uid,
                    'full_name' => $displayName ?: $username,
                    'profile_picture' => $photoURL,
                    'auth_provider' => 'google',
                    'role' => 'customer',
                    'email_verified' => 1,
                    'is_active' => 1,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                    'last_login' => Carbon::now(),
                ]);

                \Log::info('New user created:', ['user_id' => $userId]);

                return response()->json([
                    'success' => true,
                    'user_id' => $userId,
                    'role' => 'customer',
                    'message' => 'User created successfully'
                ]);
            }

        } catch (\Exception $e) {
            \Log::error('Firebase auth error:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}