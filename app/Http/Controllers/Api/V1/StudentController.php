<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/students",
     *      operationId="getStudentsList",
     *      tags={"Students"},
     *      summary="Get list of all students",
     *      description="Returns all students from the database",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      )
     * )
     */
    public function index()
    {
        $students = User::role('student')->get();
        return response()->json($students, 200);
    }

    /**
     * @OA\Get(
     *      path="/api/students/{id}",
     *      operationId="getStudentById",
     *      tags={"Students"},
     *      summary="Get student information",
     *      @OA\Parameter(
     *          name="id",
     *          description="Student ID",
     *          required=true,
     *          in="path",
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Student not found"
     *      )
     * )
     */
    public function show(User $student)
    {
        return response()->json($student, 200);
    }

    /**
     * @OA\Put(
     *      path="/api/students/{id}",
     *      operationId="updateStudent",
     *      tags={"Students"},
     *      summary="Update student information",
     *      @OA\Parameter(
     *          name="id",
     *          description="Student ID",
     *          required=true,
     *          in="path",
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(property="name", type="string", example="Jane Doe"),
     *              @OA\Property(property="email", type="string", example="jane@gmail.com"),
     *              @OA\Property(property="phone", type="string", example="+19876543210"),
     *              @OA\Property(property="bio", type="string", example="This is my updated bio")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Student updated successfully"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Student not found"
     *      )
     * )
     */
    public function update(Request $request, User $student)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $student->id,
            'phone' => 'sometimes|string|max:20',
            'bio' => 'sometimes|string',
            'address' => 'sometimes|string',
            'profile_picture' => 'sometimes|string',
        ]); 
        $student->update($data);
        return response()->json([
            'message' => 'Student updated successfully',
            'student' => $student
        ], 200);
    }


}
