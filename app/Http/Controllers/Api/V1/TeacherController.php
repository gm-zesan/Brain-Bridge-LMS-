<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Exception;



class TeacherController extends Controller
{
    protected $auth;

    public function __construct(FirebaseService $firebase)
    {
        $this->auth = $firebase->getAuth();
    }


    /**
     * @OA\Get(
     *      path="/api/teachers",
     *      operationId="getTeachersList",
     *      tags={"Teachers"},
     *      summary="Get list of all teachers",
     *      description="Returns all teachers from the database",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      )
     * )
     */
    public function index()
    {
        $teachers = User::role('teacher')->with('teacher')->get();
        return response()->json($teachers, 200);
    }


    /**
     * @OA\Post(
     *     path="/api/teachers",
     *     operationId="storeTeacher",
     *     tags={"Teachers"},
     *     summary="Create a new teacher",
     *     description="Creates a teacher and associated Firebase user",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="password", type="string", example="password123"),
     *             @OA\Property(property="title", type="string", example="Math Teacher")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Teacher created successfully"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    // public function store(Request $request)
    // {
    //     $data = $request->validate([
    //         'name' => 'required|string|max:255',
    //         'email' => 'required|string|email|max:255',
    //         'password' => 'required|string|min:6',
    //         'title' => 'nullable|string|max:255',
    //     ]);

    //     $user = User::where('email', $data['email'])->first();
    //     if (!$user) {
    //         $firebaseUser = $this->auth->createUser([
    //             'email' => $request->email,
    //             'password' => $request->password,
    //             'displayName' => $request->name,
    //         ]);

    //         $user = User::create([
    //             'name' => $data['name'],
    //             'email' => $data['email'],
    //             'firebase_uid' => $firebaseUser->uid,
    //             'password' => bcrypt($request->password)
    //         ]);
    //     }
        
    //     $user->assignRole('teacher');
    //     $token = $user->createToken('auth_token')->plainTextToken;

    //     $teacher = Teacher::create([
    //         'user_id' => $user->id,
    //         'title' => $data['title'],
    //         'teacher_level_id' => 1, // Default level
    //     ]);

    //     return response()->json([
    //         'message' => 'Teacher created successfully.',
    //         'access_token' => $token,
    //         'token_type' => 'Bearer',
    //         'data' => $teacher->load('user'),
    //     ], 201);
    // }

    public function store(Request $request)
    {
        try {
            // Validate input
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255',
                'password' => 'required|string|min:6',
                'title' => 'nullable|string|max:255',
            ]);
            DB::beginTransaction();

            // Check if user already exists
            $user = User::where('email', $data['email'])->first();
            $isNewUser = false;
            $firebaseUid = null;

            if (!$user) {
                $isNewUser = true;

                $firebaseUser = $this->auth->createUser([
                    'email' => $data['email'],
                    'password' => $data['password'],
                    'displayName' => $data['name'],
                ]);

                $firebaseUid = $firebaseUser->uid;
                
                // Create local user
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'firebase_uid' => $firebaseUid,
                    'password' => bcrypt($data['password'])
                ]);
            } 

            $existingTeacher = Teacher::where('user_id', $user->id)->first();
            
            if ($existingTeacher) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'This user is already registered as a teacher.',
                    'data' => [
                        'teacher' => $existingTeacher->load('user')
                    ]
                ], 409);
            }

            if (!$user->hasRole('teacher')) {
                $user->assignRole('teacher');
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            $teacher = Teacher::create([
                'user_id' => $user->id,
                'title' => $data['title'] ?? null,
                'teacher_level_id' => 1,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $isNewUser 
                    ? 'Teacher created successfully.' 
                    : 'Existing user converted to teacher successfully.',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'data' => $teacher->load('user'),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Registration failed. Please try again or contact support.',
            ], 500);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/teachers/{id}",
     *     operationId="showTeacher",
     *     tags={"Teachers"},
     *     summary="Get teacher by ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Teacher ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=404, description="Teacher not found")
     * )
     */
    public function show(Teacher $teacher)
    {
        return response()->json($teacher->load(['user', 'teacherLevel']));
    }


    /**
     * @OA\Put(
     *     path="/api/teachers/{id}",
     *     operationId="updateTeacher",
     *     tags={"Teachers"},
     *     summary="Update teacher info",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="introduction_video", type="string"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="bio", type="string"),
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="profile_picture", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Teacher updated successfully"),
     *     @OA\Response(response=404, description="Teacher not found")
     * )
     */
    public function update(Request $request, Teacher $teacher)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required','email','max:255',Rule::unique('users')->ignore($teacher->id)],
            'title' => 'nullable|string|max:255',
            'introduction_video' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string',
            'address' => 'nullable|string',
            'profile_picture' => 'nullable|string',
        ]);

        $userData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? $teacher->user->phone,
            'bio' => $data['bio'] ?? $teacher->user->bio,
            'address' => $data['address'] ?? $teacher->user->address,
            'profile_picture' => $data['profile_picture'] ?? $teacher->user->profile_picture,
        ];
        $teacherData = [
            'title' => $data['title'] ?? $teacher->title,
            'introduction_video' => $data['introduction_video'] ?? $teacher->introduction_video,
        ];

        $teacher->user->update($userData);
        $teacher->update($teacherData);
        return response()->json([
            'message' => 'Teacher updated successfully.',
            'data' => $teacher->load('user'),
        ]);
    }


    /**
     * @OA\Delete(
     *     path="/api/teachers/{id}",
     *     operationId="deleteTeacher",
     *     tags={"Teachers"},
     *     summary="Delete a teacher",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Teacher deleted successfully"),
     *     @OA\Response(response=404, description="Teacher not found")
     * )
     */
    public function destroy(Teacher $teacher)
    {
        $user = $teacher->user;
        $teacher->delete();
        if ($user) {
            $user->removeRole('teacher');
            $user->delete();  
        }  
        return response()->json(['message' => 'Teacher deleted successfully']);

    }
}
