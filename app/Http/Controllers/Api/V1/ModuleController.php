<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Modules",
 *     description="Manage modules for courses"
 * )
 */
class ModuleController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/modules",
     *     tags={"Modules"},
     *     summary="Get all modules (optionally filter by course_id)",
     *     @OA\Parameter(
     *         name="course_id",
     *         in="query",
     *         description="Filter modules by course ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Modules retrieved successfully")
     * )
     */
    public function index(Request $request)
    {
        $query = Module::with('course');

        if ($request->has('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        $modules = $query->latest()->get();
        return response()->json([
            'modules' => $modules,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/modules",
     *     tags={"Modules"},
     *     summary="Create a new module under a course",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"course_id","title","order_index"},
     *             @OA\Property(property="course_id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="Module 1: Introduction"),
     *             @OA\Property(property="order_index", type="integer", example=1),
     *             @OA\Property(property="description", type="string", example="Overview of Laravel basics")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Module created successfully"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Course not found or not authorized")
     * )
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order_index' => 'required|integer|min:1',
        ]);

        // Check if the logged-in teacher owns the course
        $course = Course::where('id', $data['course_id'])
                        ->where('teacher_id', Auth::id())
                        ->first();

        if (!$course) {
            return response()->json(['message' => 'Course not found or not authorized'], 404);
        }

        $module = Module::create($data);

        return response()->json([
            'message' => 'Module created successfully',
            'module' => $module,
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/modules/{id}",
     *     tags={"Modules"},
     *     summary="Get a specific module by ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Module ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Module retrieved successfully"),
     *     @OA\Response(response=404, description="Module not found")
     * )
     */
    public function show(Module $module)
    {
        if (!$module) {
            return response()->json(['message' => 'Module not found'], 404);
        }

        $module->load('course', 'videoLessons');

        return response()->json([
            'module' => $module,
        ], 200);
    }

    /**
     * @OA\Put(
     *     path="/api/modules/{id}",
     *     tags={"Modules"},
     *     summary="Update an existing module",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Module ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Updated Module Title"),
     *             @OA\Property(property="description", type="string", example="Updated description"),
     *             @OA\Property(property="order_index", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Module updated successfully"),
     *     @OA\Response(response=404, description="Module not found or not authorized")
     * )
     */
    public function update(Request $request, Module $module)
    {
        if (!$module || $module->course->teacher_id !== Auth::id()) {
            return response()->json(['message' => 'Module not found or not authorized'], 404);
        }

        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'order_index' => 'sometimes|required|integer|min:1',
        ]);

        $module->update($data);

        return response()->json([
            'message' => 'Module updated successfully',
            'module' => $module,
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/modules/{id}",
     *     tags={"Modules"},
     *     summary="Delete a module",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Module ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Module deleted successfully"),
     *     @OA\Response(response=404, description="Module not found or not authorized")
     * )
     */
    public function destroy(Module $module)
    {
        if (!$module || $module->course->teacher_id !== Auth::id()) {
            return response()->json(['message' => 'Module not found or not authorized'], 404);
        }

        $module->delete();

        return response()->json(['message' => 'Module deleted successfully'], 200);
    }
}
