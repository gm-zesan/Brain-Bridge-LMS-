<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\FirebaseService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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

            // trigger email verification if needed
            event(new Registered($user));


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


    /**
     * @OA\Put(
     *     path="/api/me",
     *     tags={"Authentication"},
     *     summary="Update authenticated user's profile",
     *     description="Update user's profile information. If the user is a teacher, can also update teacher details and skills.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="phone", type="string", example="+1234567890"),
     *             @OA\Property(property="bio", type="string", example="Experienced web developer"),
     *             @OA\Property(property="address", type="string", example="123 Main Street"),
     *             @OA\Property(property="profile_picture", type="string", example="avatars/1764352355_6929e16303767.jpg"),
     *             
     *             @OA\Property(property="title", type="string", example="Senior Teacher"),
     *             @OA\Property(property="introduction_video", type="string", example="intro_videos/intro.mp4"),
     *             @OA\Property(property="base_pay", type="number", format="float", example=50.5),
     *            @OA\Property(property="payment_method", type="string", example="paypal"),
     *            @OA\Property(property="bank_account_number", type="string", example="123456789"),
     *            @OA\Property(property="bank_routing_number", type="string", example="987654321"),
     *            @OA\Property(property="bank_name", type="string", example="Bank of Examples"),
     *            @OA\Property(property="paypal_email", type="string", example="john@example.com"),
     *            @OA\Property(property="stripe_account_id", type="string", example="acct_1Example12345"),
     *            @OA\Property(property="tax_id", type="string", example="TAX123456"),
     *             
     *             @OA\Property(
     *                 property="skills",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="skill_id", type="integer", example=3),
     *                     @OA\Property(property="years_of_experience", type="integer", example=5)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Profile updated successfully"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
    */

    public function updateMe(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $teacher = $user->teacher;

        $userData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:25',
            'bio' => 'nullable|string',
            'address' => 'nullable|string',
            'profile_picture' => 'nullable|file|image|mimes:jpeg,jpg,png,gif,svg|max:2048',
        ]);

        $teacherData = [];
        if ($teacher) {
            $teacherData = $request->validate([
                'title' => 'nullable|string|max:255',
                'introduction_video' => 'nullable|file|mimes:mp4,avi,mov|max:51200',
                'base_pay' => 'nullable|numeric|min:0',
                'payment_method' => 'nullable',
                'bank_account_number' => 'nullable|string|nullable',
                'bank_routing_number' => 'nullable|string|nullable',
                'bank_name' => 'nullable|string',
                'paypal_email' => 'nullable|email',
                'stripe_account_id' => 'nullable|string',
                'tax_id' => 'nullable|string',

                'skills' => 'nullable|array',
                'skills.*.skill_id' => 'nullable|exists:skills,id',
                'skills.*.years_of_experience' => 'nullable|numeric|min:0',
            ]);
        }

        DB::transaction(function () use ($user, $userData, $teacher, $teacherData, $request) {
            if ($request->hasFile('profile_picture')) {
                if ($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)) {
                    Storage::disk('public')->delete($user->profile_picture);
                }
                $avatar = $request->file('profile_picture');
                $avatarName = time() . '_' . uniqid() . '.' . $avatar->getClientOriginalExtension();
                $avatarPath = $avatar->storeAs('avatars', $avatarName, 'public');
                $userData['profile_picture'] = $avatarPath;
            }

            $user->update($userData);

            if ($teacher) {
                // Handle introduction video
                if ($request->hasFile('introduction_video')) {
                    if ($teacher->introduction_video && Storage::disk('public')->exists($teacher->introduction_video)) {
                        Storage::disk('public')->delete($teacher->introduction_video);
                    }
                    $video = $request->file('introduction_video');
                    $videoName = time() . '_' . uniqid() . '.' . $video->getClientOriginalExtension();
                    $videoPath = $video->storeAs('intro_videos', $videoName, 'public');
                    $teacherData['introduction_video'] = $videoPath;
                }

                $teacher->update(array_filter($teacherData, fn($value) => !is_null($value)));

                // Sync skills
                if (isset($teacherData['skills'])) {
                    $syncData = [];
                    foreach ($teacherData['skills'] as $skill) {
                        $syncData[$skill['skill_id']] = [
                            'years_of_experience' => $skill['years_of_experience'] ?? 0
                        ];
                    }
                    $teacher->skills()->sync($syncData);
                }
            }

            // Reload relations for response
            $user->load('teacher', 'teacher.teacherLevel', 'teacher.skills');
        });

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user
        ], 200);
    }



    /**
     * @OA\Get(
     *     path="/api/email/verify/{id}/{hash}",
     *     tags={"Authentication"},
     *     summary="Verify user email",
     *     description="Verifies the user's email using signed URL",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="hash",
     *         in="path",
     *         description="Signed hash from email link",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email verified successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Email verified successfully.")
     *         )
     *     )
     * )
    */
    public function verify(EmailVerificationRequest $request)
    {
        $request->fulfill();

        return response()->json([
            'status' => 'success',
            'message' => 'Email verified successfully.'
        ]);
    }




    /**
     * @OA\Post(
     *     path="/api/password/reset",
     *     operationId="resetPassword",
     *     tags={"Authentication"},
     *     summary="Reset user password",
     *     description="Sends a password reset link to the user's email",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", example="jhon@gmail.com")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Password reset link sent successfully"), 
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
    */


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

}
