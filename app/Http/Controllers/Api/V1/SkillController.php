<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use Illuminate\Http\Request;

class SkillController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/skills",
     *      operationId="getSkillsList",
     *      tags={"Skills"},
     *      summary="Get list of all skills",
     *      description="Returns all skills from the database",
     *      @OA\Response(
     *         response=200,
     *        description="Successful operation"
     *   )
     * )
     */
    public function index()
    {
        $skills = Skill::with('subject')->get();
        return response()->json($skills);
    }

    /**
     * @OA\Post(
     *     path="/api/skills",
     *    operationId="storeSkill",
     *    tags={"Skills"},
     *    summary="Create a new skill",
     *    description="Creates a new skill",
     *    @OA\RequestBody(
     *        required=true,
     *       @OA\JsonContent(
     *            required={"name"},
     *           @OA\Property(property="name", type="string", example="Problem Solving"),
     *           @OA\Property(property="subject_id", type="integer", example=1)
     *    )
     *   ),
     *   @OA\Response(response=201, description="Skill created successfully"),
     * )
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:skills,name',
            'subject_id' => 'nullable|exists:subjects,id',
        ]);

        $skill = Skill::create($data);
        return response()->json([
            'message' => 'Skill created successfully.',
            'data' => $skill
        ], 201);
    }

    /**
     * @OA\Get(
     *      path="/api/skills/{id}",
     *      operationId="getSkillById",
     *      tags={"Skills"},
     *      summary="Get skill information",
     *      description="Returns skill data",
     *      @OA\Parameter(
     *          name="id",
     *          description="Skill id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      @OA\Response(response=404, description="Skill not found")
     * )
     */
    public function show(Skill $skill)
    {
        return response()->json($skill->load('subject'));
    }

    /**
     * @OA\Put(
     *     path="/api/skills/{id}",
     *     operationId="updateSkill",
     *     tags={"Skills"},
     *     summary="Update an existing skill",
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
     *             @OA\Property(property="subject_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Skill updated successfully"),
     *     @OA\Response(response=404, description="Skill not found")
     * )
     */
    public function update(Request $request, Skill $skill)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:skills,name,' . $skill->id,
            'subject_id' => 'nullable|exists:subjects,id',
        ]);

        $skill->update($data);

        return response()->json([
            'message' => 'Skill updated successfully.',
            'data' => $skill
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/skills/{id}",
     *     operationId="deleteSkill",
     *     tags={"Skills"},
     *     summary="Delete a skill",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Skill deleted successfully"),
     *     @OA\Response(response=404, description="Skill not found")
     * )
     */
    public function destroy(Skill $skill)
    {
        $skill->delete();
        return response()->json(['message' => 'Skill deleted successfully']);
    }

}
