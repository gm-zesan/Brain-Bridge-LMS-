<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\VideoLesson;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Tag(
 *     name="Video Lessons",
 *     description="API Endpoints for managing video lessons"
 * )
 */
class VideoLessonController extends Controller
{

    protected FirebaseService $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /**
     * @OA\Get(
     *     path="/api/video-lessons",
     *     summary="Get all video lessons",
     *     tags={"Video Lessons"},
     *     @OA\Response(response=200, description="List of video lessons")
     * )
     */
    public function index()
    {
        try {
            $videoLessons = VideoLesson::with('module.course')
                ->latest()
                ->get();

            return response()->json([
                'success' => true,
                'data' => $videoLessons
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch video lessons',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/video-lessons",
     *     summary="Upload a video file and create a video lesson",
     *     tags={"Video Lessons"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"module_id","title","description","video"},
     *                 @OA\Property(property="module_id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Introduction to Laravel"),
     *                 @OA\Property(property="description", type="string", example="Getting started with Laravel basics"),
     *                 @OA\Property(property="duration_hours", type="number", example=1.5),
     *                 @OA\Property(property="video", type="string", format="binary"),
     *                 @OA\Property(property="is_published", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Video lesson created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Video uploaded successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Invalid data or upload error"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function store(Request $request)
    {
        // Validate request
        $data = $request->validate([
            'module_id' => 'required|exists:modules,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'duration_hours' => 'nullable|numeric|min:0',
            'video' => 'required|file|mimetypes:video/mp4,video/avi,video/mov,video/quicktime|max:512000', // max 500MB
            'is_published' => 'nullable|boolean',
        ]);

        try {
            $file = $request->file('video');

            // Upload to Firebase
            // $uploadResult = $this->firebaseService->uploadVideo($file, 'video-lessons');

            // if (!$uploadResult['success']) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Video upload failed',
            //         'error' => $uploadResult['error']
            //     ], 500);
            // }

            // Store video locally
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('public/videos', $filename);

            // Save video lesson in database
            $videoLesson = VideoLesson::create([
                'module_id' => $data['module_id'],
                'title' => $data['title'],
                'description' => $data['description'],
                'duration_hours' => $data['duration_hours'] ?? 0,
                // 'video_url' => $uploadResult['url'],
                // 'video_path' => $uploadResult['path'],
                // 'filename' => $uploadResult['filename'],
                'video_url' => 'videos/' . $filename,
                'video_path' => $path,
                'filename' => $filename,
                'is_published' => $data['is_published'] ?? false,
            ]);

            $videoLesson->load('module.course');

            return response()->json([
                'success' => true,
                'message' => 'Video uploaded successfully',
                'data' => $videoLesson,
            ], 201);

        } catch (\Throwable $e) {
            // Rollback: Delete uploaded video if database save fails
            if (isset($uploadResult['path'])) {
                $this->firebaseService->deleteVideo($uploadResult['path']);
            }

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during video upload',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/video-lessons/{id}",
     *     summary="Get a specific video lesson by ID",
     *     tags={"Video Lessons"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Video lesson ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200, 
     *         description="Video lesson details",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Video not found")
     * )
     */
    public function show(VideoLesson $videoLesson)
    {
        $videoLesson->load('module.course');

        return response()->json([
            'success' => true,
            'data' => $videoLesson,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/video-lessons/{id}",
     *     summary="Update an existing video lesson (use POST with _method=PUT)",
     *     tags={"Video Lessons"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Video lesson ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="_method", type="string", example="PUT"),
     *                 @OA\Property(property="title", type="string", example="Updated Laravel Basics"),
     *                 @OA\Property(property="description", type="string", example="Updated video description"),
     *                 @OA\Property(property="duration_hours", type="number", example=2),
     *                 @OA\Property(property="video", type="string", format="binary", description="New video file (optional)"),
     *                 @OA\Property(property="is_published", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Video lesson updated successfully"),
     *     @OA\Response(response=404, description="Video not found")
     * )
     */
    public function update(Request $request, VideoLesson $videoLesson)
    {
        $request->validate([
            'module_id' => 'sometimes|exists:modules,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'duration_hours' => 'nullable|numeric|min:0',
            'video' => 'sometimes|file|mimetypes:video/mp4,video/avi,video/mov,video/quicktime|max:512000', // max 500MB
            'is_published' => 'sometimes|boolean',
        ]);

        try {
            $oldVideoPath = $videoLesson->video_path;

            // If new video is uploaded
            // if ($request->hasFile('video')) {
            //     $video = $request->file('video');
                
            //     // Upload new video
            //     $uploadResult = $this->firebaseService->uploadVideo($video, 'video-lessons');

            //     if (!$uploadResult['success']) {
            //         return response()->json([
            //             'success' => false,
            //             'message' => 'Video upload failed',
            //             'error' => $uploadResult['error']
            //         ], 500);
            //     }

            //     // Update video fields
            //     $videoLesson->video_url = $uploadResult['url'];
            //     $videoLesson->video_path = $uploadResult['path'];
            //     $videoLesson->filename = $uploadResult['filename'];

            //     // Delete old video (do this after successful upload)
            //     if ($oldVideoPath) {
            //         $this->firebaseService->deleteVideo($oldVideoPath);
            //     }
            // }

            // If new video is uploaded locally
            if ($request->hasFile('video')) {
                $video = $request->file('video');
                $filename = time() . '_' . $video->getClientOriginalName();
                $path = $video->storeAs('public/videos', $filename);

                // Update video fields
                $videoLesson->video_url = 'videos/' . $filename;
                $videoLesson->video_path = $path;
                $videoLesson->filename = $filename;

                // Delete old local video if exists
                if ($oldVideoPath && Storage::exists($oldVideoPath)) {
                    Storage::delete($oldVideoPath);
                }
            }


            // Update other fields
            $videoLesson->fill($request->except('video'));
            $videoLesson->save();

            $videoLesson->load('module.course');

            return response()->json([
                'success' => true,
                'message' => 'Video lesson updated successfully',
                'data' => $videoLesson
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update video lesson',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/video-lessons/{id}",
     *     summary="Delete a video lesson",
     *     tags={"Video Lessons"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Video lesson ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200, 
     *         description="Video lesson deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Video lesson deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Video not found")
     * )
     */
    public function destroy(VideoLesson $videoLesson)
    {
        try {
            // Delete video from Firebase Storage
            // if ($videoLesson->video_path) {
            //     $this->firebaseService->deleteVideo($videoLesson->video_path);
            // }

            // Delete local video file
            if ($videoLesson->video_path && Storage::exists($videoLesson->video_path)) {
                Storage::delete($videoLesson->video_path);
            }

            // Delete from database
            $videoLesson->delete();

            return response()->json([
                'success' => true,
                'message' => 'Video lesson deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete video lesson',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
