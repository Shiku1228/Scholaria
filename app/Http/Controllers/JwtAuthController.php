<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class JwtAuthController extends Controller
{
    /**
     * Create a new token for authenticated user
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $credentials = $request->only('email', 'password');

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not create token'
            ], 500);
        }

        $user = auth()->user();
        
        return response()->json([
            'success' => true,
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl', 60) * 60,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames()
            ]
        ]);
    }

    /**
     * Get the authenticated user
     */
    public function me()
    {
        return response()->json([
            'success' => true,
            'user' => auth()->user()
        ]);
    }

    /**
     * Refresh a token
     */
    public function refresh()
    {
        try {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());
            
            return response()->json([
                'success' => true,
                'token' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl', 60) * 60
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not refresh token'
            ], 500);
        }
    }

    /**
     * Log the user out (Invalidate the token)
     */
    public function logout()
    {
        try {
            // Get the current token from the request header
            $token = request()->bearerToken();
            
            if (!$token) {
                // If no token is found, user is already logged out
                return response()->json([
                    'success' => true,
                    'message' => 'Successfully logged out'
                ]);
            }
            
            // Try to invalidate the token if it exists
            try {
                JWTAuth::invalidate($token);
            } catch (\Exception $e) {
                // If token invalidation fails, still return success
                // User might be using an expired or invalid token
                if (request()->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Successfully logged out'
                    ]);
                } else {
                    // For web requests, redirect to login page
                    return redirect()->route('login');
                }
            }
            
            // Check if request expects JSON (API) or HTML (web)
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Successfully logged out'
                ]);
            } else {
                // For web requests, redirect to login page
                return redirect()->route('login');
            }
        } catch (JWTException $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not log out: ' . $e->getMessage()
                ], 500);
            } else {
                // For web requests, still redirect to login even on error
                return redirect()->route('login');
            }
        }
    }

    /**
     * Validate token and show token info
     */
    public function validate()
    {
        try {
            $token = JWTAuth::parseToken()->getPayload();
            
            return response()->json([
                'success' => true,
                'valid' => true,
                'payload' => $token
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => 'Invalid token'
            ], 401);
        }
    }
}
