<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\TeacherLevel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;


/**
 * @OA\Tag(
 *     name="Teacher Levels",
 *     description="API Endpoints for managing Teacher Levels"
 * )
 */
class TeacherLevelController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/teacher-levels",
     *     operationId="getTeacherLevels",
     *     tags={"Teacher Levels"},
     *     summary="Get all teacher levels",
     *     description="Returns a list of all teacher levels",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index()
    {
        return response()->json(TeacherLevel::all(), 200);
    }

    /**
     * @OA\Post(
     *     path="/api/teacher-levels",
     *     operationId="storeTeacherLevel",
     *     tags={"Teacher Levels"},
     *     summary="Create a new teacher level",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"level_name"},
     *             @OA\Property(property="level_name", type="string", example="Senior"),
     *             @OA\Property(property="min_rating", type="integer", example=50),
     *             @OA\Property(property="max_rating", type="integer", example=100),
     *             @OA\Property(property="benefits", type="string", example="Access to premium resources")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Teacher level created successfully"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        // Validation rules
        $data = $request->validate([
            'level_name' => 'required|string|max:255|unique:teacher_levels,level_name',
            'min_rating' => 'nullable|integer|min:0|max:100',
            'max_rating' => 'nullable|integer|min:0|max:100',
            'benefits' => 'nullable|string',
        ]);

        $level = TeacherLevel::create($data);

        return response()->json([
            'message' => 'Teacher level created successfully.',
            'data' => $level,
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/teacher-levels/{id}",
     *     operationId="showTeacherLevel",
     *     tags={"Teacher Levels"},
     *     summary="Get a specific teacher level",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Teacher Level ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=404, description="Teacher Level not found")
     * )
     */
    public function show(TeacherLevel $teacherLevel)
    {
        return response()->json($teacherLevel, 200);
    }

    /**
     * @OA\Put(
     *     path="/api/teacher-levels/{id}",
     *     operationId="updateTeacherLevel",
     *     tags={"Teacher Levels"},
     *     summary="Update a teacher level",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Teacher Level ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="level_name", type="string", example="Senior"),
     *             @OA\Property(property="min_rating", type="integer", example=50),
     *             @OA\Property(property="max_rating", type="integer", example=100),
     *             @OA\Property(property="benefits", type="string", example="Access to premium resources")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Teacher level updated successfully"),
     *     @OA\Response(response=404, description="Teacher Level not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, TeacherLevel $teacherLevel)
    {
        $data = $request->validate([
            'level_name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('teacher_levels')->ignore($teacherLevel->id)
            ],
            'min_rating' => 'nullable|integer|min:0|max:100',
            'max_rating' => 'nullable|integer|min:0|max:100',
            'benefits' => 'nullable|string',
        ]);

        $teacherLevel->update($data);

        return response()->json([
            'message' => 'Teacher level updated successfully.',
            'data' => $teacherLevel,
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/teacher-levels/{id}",
     *     operationId="deleteTeacherLevel",
     *     tags={"Teacher Levels"},
     *     summary="Delete a teacher level",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Teacher Level ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Teacher level deleted successfully"),
     *     @OA\Response(response=404, description="Teacher Level not found")
     * )
     */
    public function destroy(TeacherLevel $teacherLevel)
    {
        $teacherLevel->delete();

        return response()->json(['message' => 'Teacher level deleted successfully.']);
    }




    /**
     * @OA\Get(
     *     path="/api/teacher-levels/{id}/progress",
     *     operationId="teacherProgress",
     *     tags={"Teacher Levels"},
     *     summary="Get teacher progress towards next level",
     *     description="Returns the current level, next level, overall progress, and detailed breakdown of requirements for promotion.",
     *     @OA\Parameter(
     *         name="teacher",
     *         in="path",
     *         description="ID of the teacher",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Progress retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="current_level", type="string", example="Silver"),
     *             @OA\Property(property="next_level", type="string", example="Gold"),
     *             @OA\Property(property="progress_percent", type="integer", example=62),
     *             @OA\Property(
     *                 property="details",
     *                 type="object",
     *                 example={
     *                     "average_rating": {"current": 4.4, "required": 4.5, "progress_percent": 97},
     *                     "five_star_reviews": {"current": 10, "required": 15, "progress_percent": 66},
     *                     "streak_good_sessions": {"current": 6, "required": 10, "progress_percent": 60}
     *                 }
     *             ),
     *             @OA\Property(property="message", type="string", example="Progress towards next level")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Teacher not found"),
     * )
     */

    public function progressToNextLevel(Teacher $teacher)
    {
        $next = $teacher->teacher_level_id + 1;

        if ($next > 5) {
            return response()->json([
                'current_level' => $teacher->teacherLevel->level_name,
                'next_level' => 'Master',
                'progress_percent' => 100,
                'details' => [],
                'message' => 'You are a Master!',
            ]);
        }

        $requirements = [
            2 => ['average_rating' => 4.3, 'total_sessions' => 5],
            3 => ['average_rating' => 4.5, 'five_star_reviews' => 15, 'streak_good_sessions' => 10],
            4 => ['average_rating' => 4.6, 'total_sessions' => 30, 'rebook_count' => 10],
            5 => ['average_rating' => 4.7, 'total_sessions' => 50, 'cancelled_sessions' => 0],
        ];

        $req = $requirements[$next] ?? [];
        [$progress, $details] = $this->calculateProgress($teacher, $req);

        return response()->json([
            'current_level' => $teacher->teacherLevel->level_name,
            'next_level' => optional(TeacherLevel::find($next))->level_name ?? 'Master',
            'progress_percent' => $progress,
            'details' => $details,
            'message' => "Progress towards next level",
        ]);
    }

    private function calculateProgress(Teacher $teacher, array $req): array
    {
        $parts = [];
        $details = [];

        foreach ($req as $key => $value) {
            $current = data_get($teacher, $key, 0);
            if ($value > 0) {
                $percent = min(100, ($current / $value) * 100);
                $parts[] = $percent;
                $details[$key] = [
                    'current' => $current,
                    'required' => $value,
                    'progress_percent' => intval($percent),
                ];
            }
        }

        $overall = count($parts) > 0 ? intval(array_sum($parts) / count($parts)) : 0;

        return [$overall, $details];
    }
}
