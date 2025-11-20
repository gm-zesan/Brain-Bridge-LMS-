<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Auth;

class FirebaseAuthController extends Controller
{

    protected $auth;

    public function __construct(FirebaseService $firebase)
    {
        $this->auth = $firebase->getAuth();
    }
    

    /**
     * @OA\Post(
     *     path="/api/register",
     *     operationId="registerUser",
     *     tags={"Authentication"},
     *     summary="Register a new user",
     *     description="Registers a new user with Firebase and creates a local user record",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="john@gmail.com"),
     *            @OA\Property(property="password", type="string", example="password123")
     *        ) 
     *    ),
     *    @OA\Response(response=201, description="User registered successfully"),
     *   @OA\Response(response=422, description="Validation error"),
     *   @OA\Response(response=500, description="Server error")
     * )
     */
    
    
    
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

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'firebase_uid' => $firebaseUser->uid,
                'password' => bcrypt($request->password),
            ]);

            $user->assignRole('admin');

            $token = $user->createToken('auth_token')->plainTextToken;
            return response()->json([
                'message' => 'Registration successful',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    /**
     * @OA\Post(
     *     path="/api/login",
     *     operationId="loginUser",
     *     tags={"Authentication"},
     *     summary="Login a user",
     *     description="Logs in a user using Firebase authentication",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", example="jhon@gmail.com"),
     *            @OA\Property(property="password", type="string", example="password123")
     *       )
     *   ),
     *   @OA\Response(response=200, description="Login successful"),
     *   @OA\Response(response=401, description="Invalid credentials"),
     *   @OA\Response(response=422, description="Validation error"),
     *   @OA\Response(response=500, description="Server error")
     * )
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $signInResult = $this->auth->signInWithEmailAndPassword(
                $request->email,
                $request->password
            );

            $firebaseUid = $signInResult->firebaseUserId();

            $user = User::where('firebase_uid', $firebaseUid)->first();
            if (!$user) {
                return response()->json(['error' => 'User not found in DB'], 404);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login successful',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user,
            ], 200);

        } catch (\Kreait\Firebase\Exception\Auth\InvalidPassword $e) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    
    /**
     * @OA\Get(
     *     path="/api/me",
     *     operationId="getAuthenticatedUser",
     *     tags={"Authentication"},
     *     summary="Get authenticated user",
     *     description="Retrieves the currently authenticated user's information",
     *     @OA\Response(response=200, description="User retrieved successfully"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function me()
    {
        $userId = Auth::id();

        if (!$userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = User::with('roles')->find($userId);
        if($user->teacher) {
            $user->load('teacher', 'teacher.teacherLevel', 'teacher.skills');
        }

        return response()->json(['user' => $user], 200);
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
            $verifiedToken = $this->auth->verifyIdToken($idToken);
            $uid = $verifiedToken->claims()->get('sub');
            $firebaseUser = $this->auth->getUser($uid);

            $user = User::firstOrCreate(
                ['firebase_uid' => $uid],
                [
                    'name' => $firebaseUser->displayName ?? 'Google User',
                    'email' => $firebaseUser->email ?? null,
                    'password' => bcrypt(bin2hex(random_bytes(16))), // dummy password
                ]
            );

            // ✅ Sanctum token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Google login successful',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user,
            ], 200);

        } catch (\Kreait\Firebase\Exception\Auth\RevokedIdToken $e) {
            return response()->json(['error' => 'Invalid or revoked token'], 401);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

}
