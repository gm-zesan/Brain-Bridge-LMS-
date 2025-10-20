<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Kreait\Firebase\Auth as FirebaseAuth;
use Illuminate\Support\Facades\Validator;

class FirebaseAuthController extends Controller
{
    protected $auth;

    public function __construct(FirebaseAuth $auth)
    {
        $this->auth = $auth;
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $firebaseUser = $this->auth->createUser([
                'email' => $request->email,
                'password' => $request->password,
                'displayName' => $request->name,
            ]);

            $token = $this->auth->createCustomToken($firebaseUser->uid);

            $this->auth->sendEmailVerificationLink($request->email);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'firebase_uid' => $firebaseUser->uid,
            ]);

            return response()->json([
                'message' => 'User registered successfully!',
                'user' => $user,
                'firebase_token' => $token->toString(),
            ], 201);

        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function login(Request $request)
    {
        $idToken = $request->bearerToken();

        if (!$idToken) {
            return response()->json(['error' => 'Token missing'], 401);
        }

        try {
            $verified = $this->auth->verifyIdToken($idToken);
            $uid = $verified->claims()->get('sub');
            $firebaseUser = $this->auth->getUser($uid);

            if (!$firebaseUser->emailVerified) {
                return response()->json(['error' => 'Email not verified'], 403);
            }

            $user = User::firstOrCreate(
                ['firebase_uid' => $uid],
                ['name' => $firebaseUser->displayName ?? 'Unknown', 'email' => $firebaseUser->email]
            );

            return response()->json([
                'message' => 'Login successful',
                'user' => $user,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Invalid token: '.$e->getMessage()], 401);
        }
    }

    public function me(Request $request)
    {
        $uid = $request->firebase_uid;
        $user = User::where('firebase_uid', $uid)->first();

        return response()->json(['user' => $user]);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $this->auth->sendPasswordResetLink($request->email);
            return response()->json(['message' => 'Password reset link sent to your email.']);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request)
    {
        $uid = $request->firebase_uid;
        try {
            $this->auth->deleteUser($uid);

            User::where('firebase_uid', $uid)->delete();

            return response()->json(['message' => 'User deleted successfully from both systems']);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function googleLogin(Request $request)
    {
        $idToken = $request->bearerToken();

        if (!$idToken) {
            return response()->json(['error' => 'Token missing'], 401);
        }

        try {
            $verified = $this->auth->verifyIdToken($idToken);
            $uid = $verified->claims()->get('sub');
            $firebaseUser = $this->auth->getUser($uid);

            $user = User::firstOrCreate(
                ['firebase_uid' => $uid],
                [
                    'name' => $firebaseUser->displayName ?? 'Google User',
                    'email' => $firebaseUser->email,
                ]
            );

            return response()->json([
                'message' => 'Google login successful',
                'user' => $user,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
