<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\VideoLesson;
use Illuminate\Http\Request;

class VideoLessonController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/video-lessons",
     *      operationId="getVideoLessonsList",
     *      tags={"Video Lessons"},
     *      summary="Get list of all video lessons",
     *      description="Returns all video lessons from the database",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      )
     * )
     */
    public function index()
    {
        return response()->json(VideoLesson::with(['teacher', 'subject'])->get());
    }

    /**
     * @OA\Post(
     *     path="/api/video-lessons",
     *     operationId="storeVideoLesson",
     *     tags={"Video Lessons"},
     *     summary="Create a new video lesson",
     *     description="Creates a new video lesson",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"teacher_id","subject_id","title","video_url"},
     *             @OA\Property(property="teacher_id", type="integer", example=1),
     *             @OA\Property(property="subject_id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="Introduction to Algebra"),
     *             @OA\Property(property="description", type="string", example="A basic introduction to algebraic concepts."),
     *             @OA\Property(property="old_price", type="number", format="float", example=49.99),
     *             @OA\Property(property="price", type="number", format="float", example=29.99),
     *             @OA\Property(property="duration_hours", type="integer", example=2),
     *             @OA\Property(property="video_url", type="string", example="https://example.com/video.mp4"),
     *             @OA\Property(property="is_published", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Video lesson created successfully"),
     * )
     */

    public function store(Request $request)
    {
        $data = $request->validate([
            'teacher_id' => 'required|exists:users,id',
            'subject_id' => 'required|exists:subjects,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'old_price' => 'nullable|numeric|min:0',
            'price' => 'nullable|numeric|min:0',
            'duration_hours' => 'nullable|integer|min:0',
            'video_url' => 'required|string',
            'is_published' => 'boolean',
        ]);

        $lesson = VideoLesson::create($data);
        return response()->json($lesson, 201);
    }

    /**
     * @OA\Get(
     *      path="/api/video-lessons/{id}",
     *      operationId="getVideoLessonById",
     *      tags={"Video Lessons"},
     *      summary="Get video lesson information",
     *      description="Returns video lesson data",
     *      @OA\Parameter(
     *          name="id",
     *          description="Video Lesson id",
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
     *      @OA\Response(response=404, description="Video Lesson not found")
     * )
     */

    public function show(VideoLesson $videoLesson)
    {
        return response()->json($videoLesson->load(['teacher', 'subject']));
    }

    /**
     * @OA\Put(
     *     path="/api/video-lessons/{id}",
     *     operationId="updateVideoLesson",
     *     tags={"Video Lessons"},
     *     summary="Update an existing video lesson",
     *     description="Updates a video lesson by ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Video Lesson ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="old_price", type="number", format="float"),
     *             @OA\Property(property="price", type="number", format="float"),
     *             @OA\Property(property="duration_hours", type="integer"),
     *             @OA\Property(property="video_url", type="string"),
     *             @OA\Property(property="is_published", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Video lesson updated successfully"),
     *     @OA\Response(response=404, description="Video Lesson not found")
     * )
     */
    public function update(Request $request, VideoLesson $videoLesson)
    {
        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'old_price' => 'nullable|numeric|min:0',
            'price' => 'nullable|numeric|min:0',
            'duration_hours' => 'nullable|integer|min:0',
            'video_url' => 'nullable|string',
            'is_published' => 'boolean',
        ]);

        $videoLesson->update($data);
        return response()->json($videoLesson);
    }


    /**
     * @OA\Delete(
     *     path="/api/video-lessons/{id}",
     *     operationId="deleteVideoLesson",
     *     tags={"Video Lessons"},
     *     summary="Delete a video lesson",
     *     description="Deletes a video lesson by ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Video Lesson ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Video lesson deleted successfully"),
     *     @OA\Response(response=404, description="Video Lesson not found")
     * )
     */
    public function destroy(VideoLesson $videoLesson)
    {
        $videoLesson->delete();
        return response()->json(['message' => 'Video lesson deleted successfully']);
    }
}
