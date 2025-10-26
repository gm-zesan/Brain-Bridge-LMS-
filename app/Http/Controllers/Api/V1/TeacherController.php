<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TeacherController extends Controller
{
    protected $auth;

    public function __construct(FirebaseService $firebase)
    {
        $this->auth = $firebase->getAuth();
    }
    public function index()
    {
        return response()->json(Teacher::with(['user', 'teacherLevel'])->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6',
            'title' => 'nullable|string|max:255',
        ]);

        $user = User::where('email', $data['email'])->first();
        if (!$user) {
            $firebaseUser = $this->auth->createUser([
                'email' => $request->email,
                'password' => $request->password,
                'displayName' => $request->name,
            ]);

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'firebase_uid' => $firebaseUser->uid,
                'password' => bcrypt($request->password),
            ]);
        }
        
        $user->assignRole('teacher');
        $token = $user->createToken('auth_token')->plainTextToken;

        $teacher = Teacher::create([
            'user_id' => $user->id,
            'title' => $data['title'],
            'teacher_level_id' => 1, // Default level
        ]);

        return response()->json([
            'message' => 'Teacher created successfully.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'data' => $teacher->load('user'),
        ], 201);
    }

    public function show(Teacher $teacher)
    {
        return response()->json($teacher->load(['user', 'teacherLevel']));
    }

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
