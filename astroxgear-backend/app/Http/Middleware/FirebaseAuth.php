<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class FirebaseAuth
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Missing token'], 401);
        }

        try {
            // Get Firebase public keys
            $keys = $this->getFirebasePublicKeys();
            
            // Decode and verify token
            $decoded = null;
            foreach ($keys as $kid => $key) {
                try {
                    $decoded = JWT::decode($token, new Key($key, 'RS256'));
                    break;
                } catch (\Exception $e) {
                    continue;
                }
            }

            if (!$decoded) {
                throw new \Exception('Invalid token');
            }

            // Get Firebase UID
            $firebaseUid = $decoded->sub;

            // Find user
            $user = User::where('firebase_uid', $firebaseUid)->first();

            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            auth()->setUser($user);
            return $next($request);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Invalid Firebase token',
                'error' => $e->getMessage()
            ], 401);
        }
    }

    private function getFirebasePublicKeys()
    {
        $url = 'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com';
        $keys = json_decode(file_get_contents($url), true);
        return $keys;
    }
}