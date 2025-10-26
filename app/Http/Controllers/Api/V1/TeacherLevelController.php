<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TeacherLevel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TeacherLevelController extends Controller
{
    public function index()
    {
        return response()->json(TeacherLevel::all(), 200);
    }

    /**
     * Store a newly created resource in storage.
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
     * Display the specified resource.
     */
    public function show(TeacherLevel $teacherLevel)
    {
        return response()->json($teacherLevel, 200);
    }

    /**
     * Update the specified resource in storage.
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
     * Remove the specified resource from storage.
     */
    public function destroy(TeacherLevel $teacherLevel)
    {
        $teacherLevel->delete();

        return response()->json(['message' => 'Teacher level deleted successfully.']);
    }
}
