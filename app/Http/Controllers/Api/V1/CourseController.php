<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Courses",
 *     description="API Endpoints for managing courses"
 * )
 */
class CourseController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/courses",
     *     tags={"Courses"},
     *     summary="Get all published courses",
     *     @OA\Response(response=200, description="List of courses retrieved successfully")
     * )
     */
    public function index()
    {
        $courses = Course::with('subject', 'teacher')
            ->where('is_published', true)
            ->latest()
            ->get();

        return response()->json([
            'courses' => $courses,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/courses",
     *     tags={"Courses"},
     *     summary="Create a new course",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title","description","subject_id","price"},
     *             @OA\Property(property="title", type="string", example="Laravel Masterclass"),
     *             @OA\Property(property="description", type="string", example="Learn Laravel from scratch"),
     *             @OA\Property(property="thumbnail_url", type="string", example="https://example.com/image.jpg"),
     *             @OA\Property(property="subject_id", type="integer", example=1),
     *             @OA\Property(property="old_price", type="number", example=4999),
     *             @OA\Property(property="price", type="number", example=2999),
     *             @OA\Property(property="is_published", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Course created successfully"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'thumbnail_url' => 'nullable|string',
            'subject_id' => 'required|exists:subjects,id',
            'old_price' => 'nullable|numeric',
            'price' => 'required|numeric',
            'is_published' => 'boolean',
        ]);

        $data['teacher_id'] = Auth::id();

        $course = Course::create($data);
        return response()->json([
            'message' => 'Course created successfully',
            'course' => $course,
        ], 201);
    }


    /**
     * @OA\Get(
     *     path="/api/courses/{id}",
     *     tags={"Courses"},
     *     summary="Get a specific course by ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Course ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Course retrieved successfully"),
     *     @OA\Response(response=404, description="Course not found")
     * )
     */
    public function show(Course $course)
    {
        if (!$course) {
            return response()->json(['message' => 'Course not found'], 404);
        }
        
        $course->load('subject', 'teacher', 'modules.videoLessons');

        return response()->json($course, 200);
    }

    /**
     * @OA\Put(
     *     path="/api/courses/{id}",
     *     tags={"Courses"},
     *     summary="Update an existing course",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Course ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Updated Laravel Masterclass"),
     *             @OA\Property(property="description", type="string", example="Updated description"),
     *             @OA\Property(property="thumbnail_url", type="string", example="https://example.com/new-image.jpg"),
     *             @OA\Property(property="subject_id", type="integer", example=2),
     *             @OA\Property(property="old_price", type="number", example=4999),
     *             @OA\Property(property="price", type="number", example=2999),
     *             @OA\Property(property="is_published", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Course updated successfully"),
     *     @OA\Response(response=404, description="Course not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function update(Request $request, Course $course)
    {
        if (!$course) {
            return response()->json(['message' => 'Course not found or not authorized'], 404);
        }

        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'thumbnail_url' => 'nullable|string',
            'subject_id' => 'sometimes|required|exists:subjects,id',
            'old_price' => 'nullable|numeric',
            'price' => 'sometimes|required|numeric',
            'is_published' => 'boolean',
        ]);

        $course->update($data);

        return response()->json([
            'message' => 'Course updated successfully',
            'course' => $course,
        ], 200);
    }


    /**
     * @OA\Delete(
     *     path="/api/courses/{id}",
     *     tags={"Courses"},
     *     summary="Delete a course",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Course ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Course deleted successfully"),
     *     @OA\Response(response=404, description="Course not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function destroy(Course $course)
    {
        if (!$course) {
            return response()->json(['message' => 'Course not found or not authorized'], 404);
        }
        $course->delete();
        return response()->json(['message' => 'Course deleted successfully'], 200);
    }
}
