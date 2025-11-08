<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Module;
use App\Models\VideoLesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
        $query = Course::with(['subject', 'teacher', 'modules', 'modules.videoLessons']);

        if(!Auth::user()->hasRole('admin')) {
            $query->where('teacher_id', Auth::id());
        }

        $courses = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $courses,
        ], 200);
    }

    


    /**
     * @OA\Post(
     *     path="/api/courses",
     *     summary="Create a new course with modules and videos",
     *     description="Creates a complete course structure including multiple modules and video lessons. Each module can contain multiple videos. Supports file uploads for course thumbnail and video files.",
     *     operationId="createCourse",
     *     tags={"Courses"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Course creation data with modules and videos",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"title", "description", "subject_id", "price", "modules"},
     *                 @OA\Property(
     *                     property="title",
     *                     type="string",
     *                     maxLength=255,
     *                     description="Course title",
     *                     example="Complete Web Development Bootcamp 2024"
     *                 ),
     *                 @OA\Property(
     *                     property="description",
     *                     type="string",
     *                     description="Detailed course description",
     *                     example="Master modern web development from scratch. Learn HTML5, CSS3, JavaScript, React, Node.js, and Laravel with hands-on projects."
     *                 ),
     *                 @OA\Property(
     *                     property="thumbnail_url",
     *                     type="string",
     *                     format="binary",
     *                     description="Course thumbnail image (JPEG, PNG, GIF - Max: 5MB)"
     *                 ),
     *                 @OA\Property(
     *                     property="subject_id",
     *                     type="integer",
     *                     description="Subject ID from subjects table",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="price",
     *                     type="number",
     *                     format="float",
     *                     description="Current course price",
     *                     example=2999.99
     *                 ),
     *                 @OA\Property(
     *                     property="old_price",
     *                     type="number",
     *                     format="float",
     *                     description="Original price before discount (optional)",
     *                     example=4999.99
     *                 ),
     *                 @OA\Property(
     *                     property="is_published",
     *                     type="boolean",
     *                     description="Publish status (default: false)",
     *                     example=true
     *                 ),
     *                 @OA\Property(
     *                     property="modules",
     *                     type="array",
     *                     description="Array of course modules (minimum 1 required)",
     *                     @OA\Items(
     *                         type="object",
     *                         required={"title", "order_index"},
     *                         @OA\Property(
     *                             property="title",
     *                             type="string",
     *                             maxLength=255,
     *                             description="Module title",
     *                             example="Introduction to Web Development"
     *                         ),
     *                         @OA\Property(
     *                             property="description",
     *                             type="string",
     *                             description="Module description (optional)",
     *                             example="Learn the fundamentals of web development and setup your development environment"
     *                         ),
     *                         @OA\Property(
     *                             property="order_index",
     *                             type="integer",
     *                             minimum=1,
     *                             description="Display order of module",
     *                             example=1
     *                         ),
     *                         @OA\Property(
     *                             property="videos",
     *                             type="array",
     *                             description="Array of video lessons for this module (optional)",
     *                             @OA\Items(
     *                                 type="object",
     *                                 required={"title", "file"},
     *                                 @OA\Property(
     *                                     property="title",
     *                                     type="string",
     *                                     maxLength=255,
     *                                     description="Video lesson title",
     *                                     example="What is HTML and How Does it Work?"
     *                                 ),
     *                                 @OA\Property(
     *                                     property="description",
     *                                     type="string",
     *                                     description="Video lesson description (optional)",
     *                                     example="Learn about HTML structure, tags, and elements with practical examples"
     *                                 ),
     *                                 @OA\Property(
     *                                     property="duration_hours",
     *                                     type="number",
     *                                     format="float",
     *                                     minimum=0,
     *                                     description="Video duration in hours (optional)",
     *                                     example=1.5
     *                                 ),
     *                                 @OA\Property(
     *                                     property="file",
     *                                     type="string",
     *                                     format="binary",
     *                                     description="Video file (MP4, AVI, MOV - Max: 500MB)"
     *                                 ),
     *                                 @OA\Property(
     *                                     property="is_published",
     *                                     type="boolean",
     *                                     description="Video publish status (default: false)",
     *                                     example=true
     *                                 )
     *                             )
     *                         )
     *                     )
     *                 ),
     *                 example={
     *                     "title": "Complete Web Development Bootcamp 2024",
     *                     "description": "Master modern web development",
     *                     "subject_id": 1,
     *                     "price": 2999.99,
     *                     "old_price": 4999.99,
     *                     "is_published": true,
     *                     "modules": {
     *                         {
     *                             "title": "HTML Fundamentals",
     *                             "description": "Learn HTML from basics to advanced",
     *                             "order_index": 1,
     *                             "videos": {
     *                                 {
     *                                     "title": "Introduction to HTML",
     *                                     "description": "What is HTML?",
     *                                     "duration_hours": 0.5,
     *                                     "is_published": true
     *                                 },
     *                                 {
     *                                     "title": "HTML Tags and Elements",
     *                                     "duration_hours": 1.0,
     *                                     "is_published": true
     *                                 }
     *                             }
     *                         },
     *                         {
     *                             "title": "CSS Styling",
     *                             "order_index": 2,
     *                             "videos": {
     *                                 {
     *                                     "title": "CSS Basics",
     *                                     "duration_hours": 0.75,
     *                                     "is_published": true
     *                                 }
     *                             }
     *                         }
     *                     }
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Course created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Course created successfully with 2 modules and 5 videos"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="course",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Complete Web Development Bootcamp 2024"),
     *                     @OA\Property(property="slug", type="string", example="complete-web-development-bootcamp-2024"),
     *                     @OA\Property(property="description", type="string", example="Master modern web development"),
     *                     @OA\Property(property="thumbnail_url", type="string", example="thumbnails/1699123456_course-thumb.jpg"),
     *                     @OA\Property(property="subject_id", type="integer", example=1),
     *                     @OA\Property(property="teacher_id", type="integer", example=5),
     *                     @OA\Property(property="price", type="number", format="float", example=2999.99),
     *                     @OA\Property(property="old_price", type="number", format="float", example=4999.99),
     *                     @OA\Property(property="is_published", type="boolean", example=true),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-07T10:30:00.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-11-07T10:30:00.000000Z")
     *                 ),
     *                 @OA\Property(
     *                     property="modules",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="course_id", type="integer", example=1),
     *                         @OA\Property(property="title", type="string", example="HTML Fundamentals"),
     *                         @OA\Property(property="description", type="string", example="Learn HTML from basics to advanced"),
     *                         @OA\Property(property="order_index", type="integer", example=1),
     *                         @OA\Property(property="created_at", type="string", format="date-time"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="videos",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="module_id", type="integer", example=1),
     *                         @OA\Property(property="title", type="string", example="Introduction to HTML"),
     *                         @OA\Property(property="description", type="string", example="What is HTML?"),
     *                         @OA\Property(property="duration_hours", type="number", format="float", example=0.5),
     *                         @OA\Property(property="video_url", type="string", example="videos/1699123456_intro-html.mp4"),
     *                         @OA\Property(property="video_path", type="string", example="public/videos/1699123456_intro-html.mp4"),
     *                         @OA\Property(property="filename", type="string", example="1699123456_intro-html.mp4"),
     *                         @OA\Property(property="is_published", type="boolean", example=true),
     *                         @OA\Property(property="created_at", type="string", format="date-time"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="statistics",
     *                     type="object",
     *                     @OA\Property(property="total_modules", type="integer", example=2),
     *                     @OA\Property(property="total_videos", type="integer", example=5),
     *                     @OA\Property(property="total_duration_hours", type="number", format="float", example=7.5)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="title",
     *                     type="array",
     *                     @OA\Items(type="string", example="The title field is required.")
     *                 ),
     *                 @OA\Property(
     *                     property="modules.0.videos.0.file",
     *                     type="array",
     *                     @OA\Items(type="string", example="The video file must be a file of type: mp4, avi, mov.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated. Please login to continue.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User not authorized as teacher",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Only teachers can create courses")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error - Course creation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to create course. Please try again."),
     *             @OA\Property(property="error", type="string", example="SQLSTATE[23000]: Integrity constraint violation")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        // dd($request->all());
        // Validate incoming request
        $validated = $request->validate([
            // Course validation
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'thumbnail_url' => 'nullable|file|image|mimes:jpeg,jpg,png,gif|max:5120', // 5MB
            'subject_id' => 'required|exists:subjects,id',
            'price' => 'required|numeric|min:0',
            'old_price' => 'nullable|numeric|min:0',
            'is_published' => 'nullable|boolean',

            // Modules validation
            'modules' => 'required|array|min:1',
            'modules.*.title' => 'required|string|max:255',
            'modules.*.description' => 'nullable|string',
            'modules.*.order_index' => 'required|integer|min:1',

            // Videos validation
            'modules.*.videos' => 'nullable|array',
            'modules.*.videos.*.title' => 'required|string|max:255',
            'modules.*.videos.*.description' => 'nullable|string',
            'modules.*.videos.*.duration_hours' => 'nullable|numeric|min:0',
            'modules.*.videos.*.file' => 'required|file|mimetypes:video/mp4,video/avi,video/mov,video/quicktime|max:512000', // 500MB
            'modules.*.videos.*.is_published' => 'nullable|boolean',
        ]);

        DB::beginTransaction();

        // Prepare course data
        $courseData = [
            'title' => $validated['title'],
            'description' => $validated['description'],
            'subject_id' => $validated['subject_id'],
            'price' => $validated['price'],
            'old_price' => $validated['old_price'] ?? null,
            'is_published' => $validated['is_published'] ?? false,
            'teacher_id' => Auth::id(),
        ];

        // Handle thumbnail upload
        if ($request->hasFile('thumbnail_url')) {
            $thumbnail = $request->file('thumbnail_url');
            $thumbnailName = time() . '_' . uniqid() . '.' . $thumbnail->getClientOriginalExtension();
            $thumbnailPath = $thumbnail->storeAs('public/thumbnails', $thumbnailName);
            $courseData['thumbnail_url'] = 'thumbnails/' . $thumbnailName;
        }

        // Create course
        $course = Course::create($courseData);

        $createdModules = [];
        $createdVideos = [];
        $totalDuration = 0;

        // Process modules and videos
        foreach ($validated['modules'] as $moduleData) {
            // Create module
            $module = Module::create([
                'course_id' => $course->id,
                'title' => $moduleData['title'],
                'description' => $moduleData['description'] ?? null,
                'order_index' => $moduleData['order_index'],
            ]);

            $createdModules[] = $module;

            // Process videos for this module
            if (!empty($moduleData['videos'])) {
                foreach ($moduleData['videos'] as $videoData) {
                    // Upload video file
                    $videoFile = $videoData['file'];
                    $videoName = time() . '_' . uniqid() . '.' . $videoFile->getClientOriginalExtension();
                    $videoPath = $videoFile->storeAs('public/videos', $videoName);

                    // Create video lesson
                    $videoLesson = VideoLesson::create([
                        'module_id' => $module->id,
                        'title' => $videoData['title'],
                        'description' => $videoData['description'] ?? null,
                        'duration_hours' => $videoData['duration_hours'] ?? 0,
                        'video_url' => 'videos/' . $videoName,
                        'video_path' => $videoPath,
                        'filename' => $videoName,
                        'is_published' => $videoData['is_published'] ?? false,
                    ]);

                    $createdVideos[] = $videoLesson;
                    $totalDuration += $videoLesson->duration_hours;
                }
            }
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Course created successfully with %d module%s and %d video%s',
            'data' => [
                'course' => $course,
                'modules' => $createdModules,
                'videos' => $createdVideos,
            ]

        ], 201);
    }

    /**
     * Cleanup uploaded files if course creation fails
     *
     * @param Request $request
     * @return void
     */
    private function cleanupFailedUpload(Request $request): void
    {
        try {
            // Delete thumbnail if uploaded
            if ($request->hasFile('thumbnail_url')) {
                $thumbnail = $request->file('thumbnail_url');
                $thumbnailName = time() . '_' . uniqid() . '.' . $thumbnail->getClientOriginalExtension();
                $thumbnailPath = 'public/thumbnails/' . $thumbnailName;
                
                if (Storage::exists($thumbnailPath)) {
                    Storage::delete($thumbnailPath);
                }
            }

            // Delete videos if uploaded
            if ($request->has('modules')) {
                foreach ($request->modules as $module) {
                    if (!empty($module['videos'])) {
                        foreach ($module['videos'] as $video) {
                            if (isset($video['file'])) {
                                $videoFile = $video['file'];
                                $videoName = time() . '_' . uniqid() . '.' . $videoFile->getClientOriginalExtension();
                                $videoPath = 'public/videos/' . $videoName;
                                
                                if (Storage::exists($videoPath)) {
                                    Storage::delete($videoPath);
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to cleanup uploaded files', [
                'error' => $e->getMessage()
            ]);
        }
    }


    
    
    // public function store(Request $request)
    // {
    //     $request->validate([
    //         // Course
    //         'title' => 'required|string|max:255',
    //         'description' => 'required|string',
    //         'thumbnail_url' => 'nullable|file|image|max:5120', // 5MB
    //         'subject_id' => 'required|exists:subjects,id',
    //         'price' => 'required|numeric',
    //         'old_price' => 'nullable|numeric',
    //         'is_published' => 'nullable|boolean',

    //         // Modules array
    //         'modules' => 'required|array|min:1',
    //         'modules.*.title' => 'required|string|max:255',
    //         'modules.*.description' => 'nullable|string',
    //         'modules.*.order_index' => 'required|integer|min:1',

    //         // Videos array inside modules
    //         'modules.*.videos' => 'nullable|array',
    //         'modules.*.videos.*.title' => 'required|string|max:255',
    //         'modules.*.videos.*.description' => 'nullable|string',
    //         'modules.*.videos.*.duration_hours' => 'nullable|numeric|min:0',
    //         'modules.*.videos.*.file' => 'required|file|mimetypes:video/mp4,video/avi,video/mov,video/quicktime|max:512000',
    //         'modules.*.videos.*.is_published' => 'nullable|boolean',
    //     ]);

    //     DB::beginTransaction();

    //     try {
    //         // 1️⃣ Create Course
    //         $courseData = [
    //             'title' => $request->title,
    //             'description' => $request->description,
    //             'subject_id' => $request->subject_id,
    //             'price' => $request->price,
    //             'old_price' => $request->old_price,
    //             'is_published' => $request->is_published ?? false,
    //             'teacher_id' => Auth::id(),
    //         ];

    //         if ($request->hasFile('thumbnail_url')) {
    //             $thumb = $request->file('thumbnail_url');
    //             $thumbName = time() . '_' . $thumb->getClientOriginalName();
    //             $thumbPath = $thumb->storeAs('public/thumbnails', $thumbName);
    //             $courseData['thumbnail_url'] = 'thumbnails/' . $thumbName;
    //         }

    //         $course = Course::create($courseData);

    //         $createdModules = [];
    //         $createdVideos = [];

    //         // 2️⃣ Create Modules and their Videos
    //         foreach ($request->modules as $moduleInput) {
    //             $module = Module::create([
    //                 'course_id' => $course->id,
    //                 'title' => $moduleInput['title'],
    //                 'description' => $moduleInput['description'] ?? null,
    //                 'order_index' => $moduleInput['order_index'],
    //             ]);

    //             $createdModules[] = $module;

    //             if (!empty($moduleInput['videos'])) {
    //                 foreach ($moduleInput['videos'] as $videoInput) {
    //                     $videoFile = $videoInput['file'];
    //                     $videoName = time() . '_' . $videoFile->getClientOriginalName();
    //                     $videoPath = $videoFile->storeAs('public/videos', $videoName);

    //                     $videoLesson = VideoLesson::create([
    //                         'module_id' => $module->id,
    //                         'title' => $videoInput['title'],
    //                         'description' => $videoInput['description'] ?? null,
    //                         'duration_hours' => $videoInput['duration_hours'] ?? 0,
    //                         'video_url' => 'videos/' . $videoName,
    //                         'video_path' => $videoPath,
    //                         'filename' => $videoName,
    //                         'is_published' => $videoInput['is_published'] ?? false,
    //                     ]);

    //                     $createdVideos[] = $videoLesson;
    //                 }
    //             }
    //         }

    //         DB::commit();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Course with modules & videos created successfully',
    //             'data' => [
    //                 'course' => $course,
    //                 'modules' => $createdModules,
    //                 'videos' => $createdVideos
    //             ]
    //         ], 201);

    //     } catch (\Throwable $e) {
    //         DB::rollBack();

    //         // Cleanup files if something failed
    //         if (isset($thumbPath) && Storage::exists($thumbPath)) {
    //             Storage::delete($thumbPath);
    //         }

    //         if (!empty($createdVideos)) {
    //             foreach ($createdVideos as $v) {
    //                 if (Storage::exists($v->video_path)) {
    //                     Storage::delete($v->video_path);
    //                 }
    //             }
    //         }

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to create course/modules/videos',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }




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
        
        $course->load('subject', 'teacher', 'modules', 'modules.videoLessons');

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
        // Validate request
        $validated = $request->validate([
            // Course validation
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'thumbnail_url' => 'nullable|file|image|mimes:jpeg,jpg,png,gif|max:5120',
            'subject_id' => 'required|exists:subjects,id',
            'price' => 'required|numeric|min:0',
            'old_price' => 'nullable|numeric|min:0',
            'is_published' => 'nullable|boolean',

            // Modules validation
            'modules' => 'required|array|min:1',
            'modules.*.id' => 'nullable|exists:modules,id',
            'modules.*.title' => 'required|string|max:255',
            'modules.*.description' => 'nullable|string',
            'modules.*.order_index' => 'required|integer|min:1',

            // Videos validation
            'modules.*.videos' => 'nullable|array',
            'modules.*.videos.*.id' => 'nullable|exists:video_lessons,id',
            'modules.*.videos.*.title' => 'required|string|max:255',
            'modules.*.videos.*.description' => 'nullable|string',
            'modules.*.videos.*.duration_hours' => 'nullable|numeric|min:0',
            'modules.*.videos.*.file' => 'nullable|file|mimetypes:video/mp4,video/avi,video/mov,video/quicktime|max:512000',
            'modules.*.videos.*.is_published' => 'nullable|boolean',
        ]);

        DB::beginTransaction();

        try {
            $course->load('modules.videoLessons');
            // Update course data
            $course->update([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'subject_id' => $validated['subject_id'],
                'price' => $validated['price'],
                'old_price' => $validated['old_price'] ?? null,
                'is_published' => $validated['is_published'] ?? false,
            ]);

            // Handle thumbnail update
            if ($request->hasFile('thumbnail_url')) {
                // Delete old thumbnail if exists
                if ($course->thumbnail_url && Storage::exists('public/' . $course->thumbnail_url)) {
                    Storage::delete('public/' . $course->thumbnail_url);
                }

                $thumbnail = $request->file('thumbnail_url');
                $thumbnailName = time() . '_' . uniqid() . '.' . $thumbnail->getClientOriginalExtension();
                $thumbnail->storeAs('public/thumbnails', $thumbnailName);
                $course->update(['thumbnail_url' => 'thumbnails/' . $thumbnailName]);
            }

            $updatedModules = [];
            $updatedVideos = [];
            $totalDuration = 0;

            // Get existing modules & videos for cleanup tracking
            $existingModuleIds = $course->modules->pluck('id')->toArray();
            $keptModuleIds = [];

            foreach ($validated['modules'] as $moduleData) {
                // Update or create module
                if (!empty($moduleData['id'])) {
                    $module = Module::find($moduleData['id']);
                    $module->update([
                        'title' => $moduleData['title'],
                        'description' => $moduleData['description'] ?? null,
                        'order_index' => $moduleData['order_index'],
                    ]);
                } else {
                    $module = Module::create([
                        'course_id' => $course->id,
                        'title' => $moduleData['title'],
                        'description' => $moduleData['description'] ?? null,
                        'order_index' => $moduleData['order_index'],
                    ]);
                }

                $keptModuleIds[] = $module->id;
                $updatedModules[] = $module;

                // Handle videos
                if (!empty($moduleData['videos'])) {
                    $existingVideoIds = $module->videoLessons->pluck('id')->toArray();
                    $keptVideoIds = [];

                    foreach ($moduleData['videos'] as $videoData) {
                        if (!empty($videoData['id'])) {
                            // Update existing video
                            $videoLesson = VideoLesson::find($videoData['id']);
                            $videoLesson->update([
                                'title' => $videoData['title'],
                                'description' => $videoData['description'] ?? null,
                                'duration_hours' => $videoData['duration_hours'] ?? 0,
                                'is_published' => $videoData['is_published'] ?? false,
                            ]);

                            // Handle video file update
                            if (!empty($videoData['file'])) {
                                if ($videoLesson->video_url && Storage::exists('public/' . $videoLesson->video_url)) {
                                    Storage::delete('public/' . $videoLesson->video_url);
                                }

                                $videoFile = $videoData['file'];
                                $videoName = time() . '_' . uniqid() . '.' . $videoFile->getClientOriginalExtension();
                                $videoFile->storeAs('public/videos', $videoName);
                                $videoLesson->update([
                                    'video_url' => 'videos/' . $videoName,
                                    'filename' => $videoName,
                                ]);
                            }
                        } else {
                            // Create new video
                            $videoFile = $videoData['file'];
                            $videoName = time() . '_' . uniqid() . '.' . $videoFile->getClientOriginalExtension();
                            $videoFile->storeAs('public/videos', $videoName);

                            $videoLesson = VideoLesson::create([
                                'module_id' => $module->id,
                                'title' => $videoData['title'],
                                'description' => $videoData['description'] ?? null,
                                'duration_hours' => $videoData['duration_hours'] ?? 0,
                                'video_url' => 'videos/' . $videoName,
                                'filename' => $videoName,
                                'is_published' => $videoData['is_published'] ?? false,
                            ]);
                        }

                        $keptVideoIds[] = $videoLesson->id;
                        $updatedVideos[] = $videoLesson;
                        $totalDuration += $videoLesson->duration_hours;
                    }

                    // Delete removed videos
                    $videosToDelete = array_diff($existingVideoIds, $keptVideoIds);
                    VideoLesson::whereIn('id', $videosToDelete)->delete();
                }
            }

            // Delete removed modules (and their videos)
            $modulesToDelete = array_diff($existingModuleIds, $keptModuleIds);
            if (!empty($modulesToDelete)) {
                $modules = Module::whereIn('id', $modulesToDelete)->get();
                foreach ($modules as $mod) {
                    $mod->videoLessons()->delete();
                    $mod->delete();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Course updated successfully',
                'data' => [
                    'course' => $course,
                    'modules' => $updatedModules,
                    'videos' => $updatedVideos,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Update failed: ' . $e->getMessage(),
            ], 500);
        }
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
        DB::beginTransaction();

        try {
            $course->load('modules.videoLessons');
            // Delete modules and their videos
            foreach ($course->modules as $module) {
                foreach ($module->videoLessons as $video) {
                    // Delete video file from storage
                    if ($video->video_url && Storage::disk('public')->exists($video->video_url)) {
                        Storage::disk('public')->delete($video->video_url);
                    }
                    $video->delete();
                }
                $module->delete();
            }

            // Delete course thumbnail if exists
            if ($course->thumbnail_url && Storage::disk('public')->exists($course->thumbnail_url)) {
                Storage::disk('public')->delete($course->thumbnail_url);
            }

            // Delete course
            $course->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Course and all related modules/videos deleted successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Delete failed: ' . $e->getMessage(),
            ], 500);
        }
    }

}
