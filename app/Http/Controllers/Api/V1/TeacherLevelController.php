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
     *     path="/api/teacher-levels/{teacher}/progress",
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
     *             @OA\Property(property="current_level", type="object",
     *                 @OA\Property(property="id", type="integer", example=2),
     *                 @OA\Property(property="name", type="string", example="Silver"),
     *                 @OA\Property(property="benefits", type="string", example="+10% Pay")
     *             ),
     *             @OA\Property(property="next_level", type="object",
     *                 @OA\Property(property="id", type="integer", example=3),
     *                 @OA\Property(property="name", type="string", example="Gold"),
     *                 @OA\Property(property="benefits", type="string", example="+20% Pay")
     *             ),
     *             @OA\Property(property="progress_percent", type="integer", example=62),
     *             @OA\Property(property="is_max_level", type="boolean", example=false),
     *             @OA\Property(
     *                 property="requirements",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="name", type="string", example="Average Rating"),
     *                     @OA\Property(property="key", type="string", example="average_rating"),
     *                     @OA\Property(property="current", type="number", example=4.4),
     *                     @OA\Property(property="required", type="number", example=4.5),
     *                     @OA\Property(property="progress_percent", type="integer", example=97),
     *                     @OA\Property(property="is_met", type="boolean", example=false)
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Progress towards next level")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Teacher not found"),
     * )
    */
    
    public function progressToNextLevel(Teacher $teacher)
    {
        $currentLevel = $teacher->teacher_level_id;
        $nextLevelId = $currentLevel + 1;

        // Max level reached
        if ($nextLevelId > 5) {
            return response()->json([
                'current_level' => [
                    'id' => $teacher->teacherLevel->id,
                    'name' => $teacher->teacherLevel->level_name,
                    'benefits' => $teacher->teacherLevel->benefits,
                ],
                'next_level' => null,
                'progress_percent' => 100,
                'is_max_level' => true,
                'requirements' => [],
                'message' => 'Congratulations! You have reached the Master level!',
            ]);
        }

        // Get next level requirements
        $requirements = $this->getLevelRequirements($nextLevelId);
        
        // Calculate progress
        [$overallProgress, $requirementDetails] = $this->calculateProgress($teacher, $requirements);

        // Get next level info
        $nextLevel = TeacherLevel::find($nextLevelId);

        return response()->json([
            'current_level' => [
                'id' => $teacher->teacherLevel->id,
                'name' => $teacher->teacherLevel->level_name,
                'benefits' => $teacher->teacherLevel->benefits,
            ],
            'next_level' => [
                'id' => $nextLevel->id,
                'name' => $nextLevel->level_name,
                'benefits' => $nextLevel->benefits,
            ],
            'progress_percent' => $overallProgress,
            'is_max_level' => false,
            'requirements' => $requirementDetails,
            'message' => "Keep up the great work! You're {$overallProgress}% towards {$nextLevel->level_name}",
        ]);
    }

    /**
     * Get requirements for a specific level
     */
    private function getLevelRequirements(int $level): array
    {
        $allRequirements = [
            2 => [ // Silver
                'average_rating' => ['name' => 'Average Rating', 'value' => 4.3],
                'total_sessions' => ['name' => 'Total Sessions', 'value' => 5],
            ],
            3 => [ // Gold
                'average_rating' => ['name' => 'Average Rating', 'value' => 4.5],
                'five_star_reviews' => ['name' => 'Five Star Reviews', 'value' => 15],
                'streak_good_sessions' => ['name' => 'Good Session Streak', 'value' => 10],
            ],
            4 => [ // Platinum
                'average_rating' => ['name' => 'Average Rating', 'value' => 4.6],
                'total_sessions' => ['name' => 'Total Sessions', 'value' => 30],
                'rebook_count' => ['name' => 'Rebook Count', 'value' => 10],
            ],
            5 => [ // Master
                'average_rating' => ['name' => 'Average Rating', 'value' => 4.7],
                'total_sessions' => ['name' => 'Total Sessions', 'value' => 50],
                'cancelled_sessions' => ['name' => 'Zero Cancellations', 'value' => 0],
            ],
        ];

        return $allRequirements[$level] ?? [];
    }

    /**
     * Calculate progress for all requirements
     */
    private function calculateProgress(Teacher $teacher, array $requirements): array
    {
        if (empty($requirements)) {
            return [0, []];
        }

        $progressValues = [];
        $details = [];

        foreach ($requirements as $key => $requirement) {
            $current = data_get($teacher, $key, 0);
            $required = $requirement['value'];
            
            // Special handling for cancellations (lower is better)
            if ($key === 'cancelled_sessions') {
                $percent = $current <= $required ? 100 : 0;
                $isMet = $current <= $required;
            } else {
                $percent = $required > 0 ? min(100, ($current / $required) * 100) : 0;
                $isMet = $current >= $required;
            }
            
            $progressValues[] = $percent;
            
            $details[] = [
                'name' => $requirement['name'],
                'key' => $key,
                'current' => $current,
                'required' => $required,
                'progress_percent' => intval($percent),
                'is_met' => $isMet,
            ];
        }

        $overallProgress = intval(array_sum($progressValues) / count($progressValues));

        return [$overallProgress, $details];
    }

}
