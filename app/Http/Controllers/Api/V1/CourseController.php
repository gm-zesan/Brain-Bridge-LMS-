<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\UploadVideoToFirebase;
use App\Mail\CourseEnrolledMail;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Module;
use App\Models\VideoLesson;
use App\Services\FirebaseService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Tag(
 *     name="Courses",
 *     description="API Endpoints for managing courses"
 * )
 */
class CourseController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * @OA\Get(
     *     path="/api/public-courses",
     *     summary="Get all published courses",
     *     description="Retrieve all published courses available for public access. Includes course details, teacher information, modules, and video lessons.",
     *     operationId="getAllPublicCourses",
     *     tags={"Courses"},
     *     @OA\Response(
     *         response=200,
     *         description="List of published courses retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Complete Web Development Bootcamp"),
     *                     @OA\Property(property="description", type="string", example="Learn web development from scratch with HTML, CSS, JavaScript, and more"),
     *                     @OA\Property(property="subject_id", type="integer", example=5),
     *                     @OA\Property(property="teacher_id", type="integer", example=10),
     *                     @OA\Property(property="price", type="number", format="float", example=99.99),
     *                     @OA\Property(property="duration_weeks", type="integer", example=12, nullable=true),
     *                     @OA\Property(property="difficulty_level", type="string", example="beginner", enum={"beginner", "intermediate", "advanced"}),
     *                     @OA\Property(property="thumbnail_url", type="string", example="https://example.com/thumbnails/course1.jpg", nullable=true),
     *                     @OA\Property(property="is_published", type="boolean", example=true),
     *                     @OA\Property(property="enrollment_count", type="integer", example=150),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-01T10:30:00.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-11-16T12:45:00.000000Z"),
     *                     @OA\Property(
     *                         property="subject",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=5),
     *                         @OA\Property(property="name", type="string", example="Web Development"),
     *                         @OA\Property(property="description", type="string", example="Learn modern web development technologies", nullable=true),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z")
     *                     ),
     *                     @OA\Property(
     *                         property="teacher",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=10),
     *                         @OA\Property(property="name", type="string", example="John Doe"),
     *                         @OA\Property(property="email", type="string", example="john@example.com"),
     *                         @OA\Property(property="role", type="string", example="teacher"),
     *                         @OA\Property(property="profile_picture", type="string", example="https://example.com/profiles/john.jpg", nullable=true),
     *                         @OA\Property(property="bio", type="string", example="Experienced web developer with 10+ years", nullable=true),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-15T08:30:00.000000Z"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2024-11-10T14:20:00.000000Z")
     *                     ),
     *                     @OA\Property(
     *                         property="modules",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="course_id", type="integer", example=1),
     *                             @OA\Property(property="title", type="string", example="Introduction to HTML"),
     *                             @OA\Property(property="description", type="string", example="Learn the basics of HTML structure and tags", nullable=true),
     *                             @OA\Property(property="order", type="integer", example=1),
     *                             @OA\Property(property="duration_minutes", type="integer", example=120, nullable=true),
     *                             @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-01T11:00:00.000000Z"),
     *                             @OA\Property(property="updated_at", type="string", format="date-time", example="2024-11-01T11:00:00.000000Z"),
     *                             @OA\Property(
     *                                 property="video_lessons",
     *                                 type="array",
     *                                 @OA\Items(
     *                                     type="object",
     *                                     @OA\Property(property="id", type="integer", example=1),
     *                                     @OA\Property(property="module_id", type="integer", example=1),
     *                                     @OA\Property(property="title", type="string", example="HTML Basics - Part 1"),
     *                                     @OA\Property(property="description", type="string", example="Introduction to HTML tags and structure", nullable=true),
     *                                     @OA\Property(property="video_url", type="string", example="https://example.com/videos/lesson1.mp4"),
     *                                     @OA\Property(property="duration_seconds", type="integer", example=1800),
     *                                     @OA\Property(property="order", type="integer", example=1),
     *                                     @OA\Property(property="is_preview", type="boolean", example=true),
     *                                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-01T11:30:00.000000Z"),
     *                                     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-11-01T11:30:00.000000Z")
     *                                 )
     *                             )
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to retrieve courses")
     *         )
     *     )
     * )
    */

    public function allCourses()
    {
        $courses = Course::with(['subject', 'teacher', 'modules', 'modules.videoLessons'])
            ->where('is_published', true)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $courses,
        ], 200);
    }

    
    /**
     * @OA\Get(
     *     path="/api/public-courses/{id}",
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

    public function courseDetails(Course $course)
    {
        if (!$course) {
            return response()->json(['message' => 'Course not found'], 404);
        }
        
        $course->load('subject', 'teacher', 'modules', 'modules.videoLessons');

        $course->modules->transform(function ($module) {
            $module->videoLessons->transform(function ($video, $index) {
                $video->is_accessible = $index === 0;
                if ($index !== 0) {
                    unset($video->video_url);
                }
                return $video;
            });
            return $module;
        });

        return response()->json($course, 200);
    }



    /**
     * @OA\Post(
     *     path="/api/courses/payment-intent",
     *     tags={"Course Payment"},
     *     summary="Create payment intent for course purchase",
     *     description="Creates a Stripe payment intent for purchasing a course. Returns payment details or indicates if the course is free.",
     *     
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"course_id"},
     *             @OA\Property(property="course_id", type="integer", example=1, description="ID of the course to purchase")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment intent created successfully or free course",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="requires_payment", type="boolean", example=true),
     *             @OA\Property(property="client_secret", type="string", example="pi_xxxxx_secret_xxxxx"),
     *             @OA\Property(property="payment_intent_id", type="string", example="pi_xxxxx"),
     *             @OA\Property(property="amount", type="number", format="float", example=99.99),
     *             @OA\Property(
     *                 property="course",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Complete Web Development Course"),
     *                 @OA\Property(property="subject", type="string", example="Programming"),
     *                 @OA\Property(property="teacher", type="string", example="John Doe"),
     *                 @OA\Property(property="price", type="number", format="float", example=99.99),
     *                 @OA\Property(property="old_price", type="number", format="float", example=149.99, nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Course not available or already purchased",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You already own this course")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Course not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Course not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to create payment intent"),
     *             @OA\Property(property="error", type="string", example="Error details")
     *         )
     *     )
     * )
    */

    public function createCoursePaymentIntent(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
        ]);

        try {
            $course = Course::with('subject', 'teacher')
                ->findOrFail($validated['course_id']);

            // Check if course is published
            if (!$course->is_published) {
                return response()->json(['message' => 'This course is not available for purchase'], 400);
            }

            // Check if user already purchased this course
            if (CourseEnrollment::where('course_id', $course->id)
                    ->where('student_id', Auth::id())
                    ->exists()) {
                return response()->json(['message' => 'You already own this course'], 400);
            }

            // Check if price is zero (free course)
            if ($course->price <= 0) {
                return response()->json([
                    'success' => true,
                    'requires_payment' => false,
                    'message' => 'This is a free course',
                ]);
            }

            // Create payment intent
            $paymentIntent = $this->paymentService->createPaymentIntent(
                $course->price,
                'usd',
                [
                    'course_id' => $course->id,
                    'student_id' => Auth::id(),
                    'teacher_id' => $course->teacher_id,
                    'course_title' => $course->title,
                    'type' => 'course_purchase',
                ]
            );

            if (!$paymentIntent['success']) {
                return response()->json([
                    'message' => 'Failed to create payment intent',
                    'error' => $paymentIntent['error']
                ], 500);
            }

            return response()->json([
                'success' => true,
                'requires_payment' => true,
                'client_secret' => $paymentIntent['client_secret'],
                'payment_intent_id' => $paymentIntent['payment_intent_id'],
                'amount' => $paymentIntent['amount'],
                'course' => [
                    'id' => $course->id,
                    'title' => $course->title,
                    'subject' => $course->subject->name ?? 'Course',
                    'teacher' => $course->teacher->name,
                    'price' => $course->price,
                    'old_price' => $course->old_price,
                ]
            ]);

        } catch (\Exception $e) {
            
            return response()->json([
                'message' => 'Failed to create payment intent'
            ], 500);
        }
    }



    /**
     * @OA\Post(
     *     path="/api/courses/confirm-purchase",
     *     tags={"Course Payment"},
     *     summary="Confirm course purchase",
     *     description="Confirms the course purchase after payment verification. Creates enrollment record and sends confirmation emails to both student and teacher.",
     *     
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"course_id"},
     *             @OA\Property(property="course_id", type="integer", example=1, description="ID of the course to purchase"),
     *             @OA\Property(property="payment_intent_id", type="string", example="pi_xxxxx", description="Stripe payment intent ID (required for paid courses)", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Course purchase confirmed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Course purchased successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="enrollment_id", type="integer", example=123),
     *                 @OA\Property(property="course_id", type="integer", example=1),
     *                 @OA\Property(property="payment_status", type="string", example="paid", enum={"free", "paid"}),
     *                 @OA\Property(property="amount_paid", type="number", format="float", example=99.99)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Validation or business logic error",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     @OA\Property(property="message", type="string", example="This course is not available")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="message", type="string", example="You already own this course")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="message", type="string", example="Payment intent ID is required")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="message", type="string", example="Failed to verify payment")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="message", type="string", example="Payment not completed"),
     *                     @OA\Property(property="payment_status", type="string", example="processing")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Course not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Course not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error - Transaction rolled back",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Purchase confirmation failed. Please contact support.")
     *         )
     *     )
     * )
    */

    public function confirmCoursePurchase(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'payment_intent_id' => 'nullable|string', // nullable for free courses
        ]);

        DB::beginTransaction();
        try {
            $course = Course::with('teacher', 'subject')
                ->lockForUpdate()
                ->findOrFail($validated['course_id']);

            // Re-check if course is published
            if (!$course->is_published) {
                DB::rollBack();
                return response()->json(['message' => 'This course is not available'], 400);
            }

            // Re-check duplicate purchase
            if (CourseEnrollment::where('course_id', $course->id)
                    ->where('student_id', Auth::id())
                    ->exists()) {
                DB::rollBack();
                return response()->json(['message' => 'You already own this course'], 400);
            }

            // Verify payment if required
            $paymentStatus = 'free';
            $paymentIntentId = null;
            $amountPaid = 0;

            if ($course->price > 0) {
                if (!$validated['payment_intent_id']) {
                    DB::rollBack();
                    return response()->json(['message' => 'Payment intent ID is required'], 400);
                }

                // Verify payment with Stripe
                $paymentResult = $this->paymentService->getPaymentIntent($validated['payment_intent_id']);

                if (!$paymentResult['success']) {
                    DB::rollBack();
                    return response()->json(['message' => 'Failed to verify payment'], 400);
                }

                if ($paymentResult['status'] !== 'succeeded') {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Payment not completed',
                        'payment_status' => $paymentResult['status']
                    ], 400);
                }

                $paymentStatus = 'paid';
                $paymentIntentId = $validated['payment_intent_id'];
                $amountPaid = $paymentResult['amount'];
            }

            // Create course enrollment
            $enrollment = CourseEnrollment::create([
                'course_id' => $course->id,
                'student_id' => Auth::id(),
                'teacher_id' => $course->teacher_id,
                'enrolled_at' => now(),
                'payment_status' => $paymentStatus,
                'payment_intent_id' => $paymentIntentId,
                'payment_method' => $paymentIntentId ? 'stripe' : null,
                'amount_paid' => $amountPaid,
                'currency' => 'usd',
                'paid_at' => $paymentStatus === 'paid' ? now() : null,
                'status' => 'active',
            ]);

            // Increment enrollment count
            $course->increment('enrollment_count');

            DB::commit();

            // Send emails AFTER commit
            Mail::to($course->teacher->email)->queue(new CourseEnrolledMail($enrollment, 'teacher'));
            Mail::to(Auth::user()->email)->queue(new CourseEnrolledMail($enrollment, 'student'));

            return response()->json([
                'success' => true,
                'message' => 'Course purchased successfully',
                'data' => [
                    'enrollment_id' => $enrollment->id,
                    'course_id' => $course->id,
                    'payment_status' => $paymentStatus,
                    'amount_paid' => $amountPaid,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Purchase confirmation failed. Please contact support.'
            ], 500);
        }
    }


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

        try {
            // Prepare course data
            $courseData = [
                'title' => $validated['title'],
                'description' => $validated['description'],
                'subject_id' => $validated['subject_id'],
                'price' => $validated['price'],
                'old_price' => $validated['old_price'] ?? null,
                'is_published' => false, // Keep unpublished until videos upload
                'teacher_id' => Auth::id(),
            ];

            // Handle thumbnail upload
            if ($request->hasFile('thumbnail_url')) {
                $thumbnail = $request->file('thumbnail_url');
                $thumbnailName = time() . '_' . uniqid() . '.' . $thumbnail->getClientOriginalExtension();
                $thumbnailPath = $thumbnail->storeAs('thumbnails', $thumbnailName);
                $courseData['thumbnail_url'] = 'thumbnails/' . $thumbnailName;
            }

            // Create course
            $course = Course::create($courseData);

            $createdModules = [];
            $queuedVideos = 0;

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
                        $videoFile = $videoData['file'];
                        
                        // Save video temporarily to local storage
                        $tempFileName = time() . '_' . uniqid() . '.' . $videoFile->getClientOriginalExtension();
                        $tempPath = $videoFile->storeAs('temp_videos', $tempFileName, 'local');

                        // Create video lesson with pending status
                        $videoLesson = VideoLesson::create([
                            'module_id' => $module->id,
                            'title' => $videoData['title'],
                            'description' => $videoData['description'] ?? null,
                            'duration_hours' => $videoData['duration_hours'] ?? 0,
                            'video_path' => null, // Will be set after upload
                            'filename' => $videoFile->getClientOriginalName(),
                            'file_size' => $videoFile->getSize(),
                            'mime_type' => $videoFile->getMimeType(),
                            'upload_status' => 'pending', // pending, processing, completed, failed
                            'temp_path' => $tempPath, // Store temp path
                            'is_published' => false, // Don't publish until uploaded
                        ]);

                        // Queue the video upload job
                        UploadVideoToFirebase::dispatch(
                            $videoLesson->id,
                            $tempPath,
                            $course->id
                        )->onQueue('video-uploads');

                        $queuedVideos++;
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Course created successfully. Videos are being uploaded in the background.',
                'data' => [
                    'course' => $course->fresh(),
                    'modules_count' => count($createdModules),
                    'videos_queued' => $queuedVideos,
                    'status' => 'Videos are being processed. Check back in a few minutes.'
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Course creation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/courses/{courseId}/upload-status",
     *     tags={"Course Management"},
     *     summary="Get video upload status for a course",
     *     description="Returns detailed upload status information for all videos in a course including completion progress, processing status, and failure counts. Useful for tracking bulk video upload progress.",
     *     @OA\Parameter(
     *         name="courseId",
     *         in="path",
     *         required=true,
     *         description="ID of the course to check upload status",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Upload status retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="course_id", type="integer", example=1, description="ID of the course"),
     *                 @OA\Property(property="total_videos", type="integer", example=15, description="Total number of videos in the course"),
     *                 @OA\Property(property="completed", type="integer", example=12, description="Number of videos successfully uploaded"),
     *                 @OA\Property(property="processing", type="integer", example=2, description="Number of videos currently being processed"),
     *                 @OA\Property(property="failed", type="integer", example=1, description="Number of videos that failed to upload"),
     *                 @OA\Property(property="progress_percentage", type="number", format="float", example=80.00, description="Percentage of videos completed (0-100)"),
     *                 @OA\Property(property="is_complete", type="boolean", example=false, description="Whether all videos have been successfully uploaded")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User doesn't have access to this course",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You don't have permission to access this course")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Course not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Course not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to retrieve upload status")
     *         )
     *     )
     * )
    */
    
    public function getUploadStatus($courseId)
    {
        $course = Course::with(['modules.videoLessons'])->findOrFail($courseId);

        $totalVideos = 0;
        $completedVideos = 0;
        $failedVideos = 0;
        $processingVideos = 0;

        foreach ($course->modules as $module) {
            foreach ($module->videoLessons as $video) {
                $totalVideos++;
                
                switch ($video->upload_status) {
                    case 'completed':
                        $completedVideos++;
                        break;
                    case 'failed':
                        $failedVideos++;
                        break;
                    case 'processing':
                        $processingVideos++;
                        break;
                }
            }
        }

        $progress = $totalVideos > 0 ? round(($completedVideos / $totalVideos) * 100, 2) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'course_id' => $course->id,
                'total_videos' => $totalVideos,
                'completed' => $completedVideos,
                'processing' => $processingVideos,
                'failed' => $failedVideos,
                'progress_percentage' => $progress,
                'is_complete' => $completedVideos === $totalVideos && $totalVideos > 0,
            ]
        ]);
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
        
        $course->load('subject', 'teacher', 'modules', 'modules.videoLessons');

        return response()->json($course, 200);
    }

    /**
     * @OA\Put(
     *     path="/api/courses/{courseId}",
     *     tags={"Courses"},
     *     summary="Update course with modules and videos",
     *     description="Updates an existing course including its modules and videos. Supports background video upload for new/replaced videos. Can add, update, or delete modules and videos. Only the course owner can update.",
     *     
     *     @OA\Parameter(
     *         name="courseId",
     *         in="path",
     *         required=true,
     *         description="ID of the course to update",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Course update data with modules and videos. Use multipart/form-data for file uploads.",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"title", "description", "subject_id", "price", "modules"},
     *                 @OA\Property(property="title", type="string", maxLength=255, example="Complete Web Development Course - Updated"),
     *                 @OA\Property(property="description", type="string", example="Learn full-stack web development from scratch with updated content"),
     *                 @OA\Property(property="thumbnail_url", type="string", format="binary", description="New course thumbnail image (optional, jpeg/jpg/png/gif, max 5MB)"),
     *                 @OA\Property(property="subject_id", type="integer", example=1),
     *                 @OA\Property(property="price", type="number", format="float", example=129.99),
     *                 @OA\Property(property="old_price", type="number", format="float", example=199.99, nullable=true),
     *                 @OA\Property(property="is_published", type="boolean", example=true),
     *                 @OA\Property(
     *                     property="modules",
     *                     type="array",
     *                     description="Array of modules. Include 'id' for existing modules, omit for new ones.",
     *                     @OA\Items(
     *                         type="object",
     *                         required={"title", "order_index"},
     *                         @OA\Property(property="id", type="integer", example=1, description="Module ID (required for existing modules)"),
     *                         @OA\Property(property="title", type="string", maxLength=255, example="Advanced JavaScript"),
     *                         @OA\Property(property="description", type="string", example="Deep dive into JavaScript", nullable=true),
     *                         @OA\Property(property="order_index", type="integer", minimum=1, example=1),
     *                         @OA\Property(property="action", type="string", enum={"keep", "delete"}, example="keep", description="Action to perform on this module"),
     *                         @OA\Property(
     *                             property="videos",
     *                             type="array",
     *                             description="Array of videos. Include 'id' for existing videos, omit for new ones.",
     *                             @OA\Items(
     *                                 type="object",
     *                                 required={"title"},
     *                                 @OA\Property(property="id", type="integer", example=5, description="Video ID (required for existing videos)"),
     *                                 @OA\Property(property="title", type="string", maxLength=255, example="ES6 Features"),
     *                                 @OA\Property(property="description", type="string", example="Learn about ES6 syntax", nullable=true),
     *                                 @OA\Property(property="duration_hours", type="number", format="float", example=2.5, nullable=true),
     *                                 @OA\Property(property="file", type="string", format="binary", description="New/replacement video file (optional, mp4/avi/mov/quicktime, max 500MB)"),
     *                                 @OA\Property(property="is_published", type="boolean", example=true),
     *                                 @OA\Property(property="action", type="string", enum={"keep", "update", "delete"}, example="keep", description="keep=no changes, update=replace video, delete=remove video")
     *                             )
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Course updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Course updated successfully. 2 video(s) are being uploaded in the background."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="course",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Complete Web Development Course - Updated"),
     *                     @OA\Property(property="description", type="string", example="Learn full-stack web development"),
     *                     @OA\Property(property="thumbnail_url", type="string", example="thumbnails/1234567890_abc123.jpg"),
     *                     @OA\Property(property="subject_id", type="integer", example=1),
     *                     @OA\Property(property="price", type="number", format="float", example=129.99),
     *                     @OA\Property(property="old_price", type="number", format="float", example=199.99),
     *                     @OA\Property(property="is_published", type="boolean", example=true),
     *                     @OA\Property(property="teacher_id", type="integer", example=10)
     *                 ),
     *                 @OA\Property(property="modules_count", type="integer", example=5),
     *                 @OA\Property(property="videos_queued", type="integer", example=2, description="Number of videos being uploaded in background"),
     *                 @OA\Property(property="status", type="string", example="Some videos are being processed. Check upload status endpoint.")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="title",
     *                     type="array",
     *                     @OA\Items(type="string", example="The title field is required.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Not the course owner",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthorized to update this course")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Course not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Course not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Update failed: Database connection error")
     *         )
     *     )
     * )
    */

    public function update(Request $request, Course $course)
    {
        // Check authorization
        if ($course->teacher_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this course'
            ], 403);
        }

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
            'modules.*.action' => 'nullable|string|in:keep,delete', // New field

            // Videos validation
            'modules.*.videos' => 'nullable|array',
            'modules.*.videos.*.id' => 'nullable|exists:video_lessons,id',
            'modules.*.videos.*.title' => 'required|string|max:255',
            'modules.*.videos.*.description' => 'nullable|string',
            'modules.*.videos.*.duration_hours' => 'nullable|numeric|min:0',
            'modules.*.videos.*.file' => 'nullable|file|mimetypes:video/mp4,video/avi,video/mov,video/quicktime|max:512000',
            'modules.*.videos.*.is_published' => 'nullable|boolean',
            'modules.*.videos.*.action' => 'nullable|string|in:keep,update,delete', // New field
        ]);

        DB::beginTransaction();

        try {
            $course->load('modules.videoLessons');
            $firebaseService = app(FirebaseService::class);

            // Update course data
            $course->update([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'subject_id' => $validated['subject_id'],
                'price' => $validated['price'],
                'old_price' => $validated['old_price'] ?? null,
                'is_published' => $validated['is_published'] ?? $course->is_published,
            ]);

            // Handle thumbnail update
            if ($request->hasFile('thumbnail_url')) {
                // Delete old thumbnail if exists
                if ($course->thumbnail_url && Storage::exists($course->thumbnail_url)) {
                    Storage::delete($course->thumbnail_url);
                }

                $thumbnail = $request->file('thumbnail_url');
                $thumbnailName = time() . '_' . uniqid() . '.' . $thumbnail->getClientOriginalExtension();
                $thumbnail->storeAs('thumbnails', $thumbnailName);
                $course->update(['thumbnail_url' => 'thumbnails/' . $thumbnailName]);
            }

            $updatedModules = [];
            $queuedVideos = 0;
            $immediateVideos = 0;

            // Get existing modules for cleanup tracking
            $existingModuleIds = $course->modules->pluck('id')->toArray();
            $keptModuleIds = [];

            foreach ($validated['modules'] as $moduleData) {
                // Check if module should be deleted
                if (isset($moduleData['action']) && $moduleData['action'] === 'delete') {
                    if (!empty($moduleData['id'])) {
                        $moduleToDelete = Module::find($moduleData['id']);
                        if ($moduleToDelete) {
                            // Delete all videos in this module
                            foreach ($moduleToDelete->videoLessons as $video) {
                                if ($video->video_path) {
                                    $firebaseService->deleteFile($video->video_path);
                                }
                                // Delete temp files if any
                                if ($video->temp_path && Storage::disk('local')->exists($video->temp_path)) {
                                    Storage::disk('local')->delete($video->temp_path);
                                }
                            }
                            $moduleToDelete->videoLessons()->delete();
                            $moduleToDelete->delete();
                        }
                    }
                    continue;
                }

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
                        $action = $videoData['action'] ?? 'keep';

                        // Delete video
                        if ($action === 'delete' && !empty($videoData['id'])) {
                            $videoToDelete = VideoLesson::find($videoData['id']);
                            if ($videoToDelete) {
                                // Delete from Firebase
                                if ($videoToDelete->video_path) {
                                    $firebaseService->deleteFile($videoToDelete->video_path);
                                }
                                // Delete temp files
                                if ($videoToDelete->temp_path && Storage::disk('local')->exists($videoToDelete->temp_path)) {
                                    Storage::disk('local')->delete($videoToDelete->temp_path);
                                }
                                $videoToDelete->delete();
                            }
                            continue;
                        }

                        // Update existing video
                        if (!empty($videoData['id'])) {
                            $videoLesson = VideoLesson::find($videoData['id']);
                            
                            // Update basic info
                            $videoLesson->update([
                                'title' => $videoData['title'],
                                'description' => $videoData['description'] ?? null,
                                'duration_hours' => $videoData['duration_hours'] ?? 0,
                                'is_published' => $videoData['is_published'] ?? false,
                            ]);

                            // Handle video file replacement
                            if (!empty($videoData['file'])) {
                                $videoFile = $videoData['file'];

                                // Delete old video from Firebase (if exists)
                                if ($videoLesson->video_path) {
                                    $firebaseService->deleteFile($videoLesson->video_path);
                                }

                                // Delete old temp file (if exists)
                                if ($videoLesson->temp_path && Storage::disk('local')->exists($videoLesson->temp_path)) {
                                    Storage::disk('local')->delete($videoLesson->temp_path);
                                }

                                // Save new video temporarily
                                $tempFileName = time() . '_' . uniqid() . '.' . $videoFile->getClientOriginalExtension();
                                $tempPath = $videoFile->storeAs('temp_videos', $tempFileName, 'local');

                                // Update video with pending status
                                $videoLesson->update([
                                    'filename' => $videoFile->getClientOriginalName(),
                                    'file_size' => $videoFile->getSize(),
                                    'mime_type' => $videoFile->getMimeType(),
                                    'upload_status' => 'pending',
                                    'temp_path' => $tempPath,
                                    'video_path' => null,
                                    'is_published' => false, // Unpublish until upload completes
                                    'upload_error' => null,
                                ]);

                                // Queue the upload
                                UploadVideoToFirebase::dispatch(
                                    $videoLesson->id,
                                    $tempPath,
                                    $course->id
                                )->onQueue('video-uploads');

                                $queuedVideos++;
                            }

                            $keptVideoIds[] = $videoLesson->id;

                        } else {
                            // Create new video
                            if (empty($videoData['file'])) {
                                continue; // Skip if no file provided for new video
                            }

                            $videoFile = $videoData['file'];

                            // Save video temporarily
                            $tempFileName = time() . '_' . uniqid() . '.' . $videoFile->getClientOriginalExtension();
                            $tempPath = $videoFile->storeAs('temp_videos', $tempFileName, 'local');

                            // Create video lesson with pending status
                            $videoLesson = VideoLesson::create([
                                'module_id' => $module->id,
                                'title' => $videoData['title'],
                                'description' => $videoData['description'] ?? null,
                                'duration_hours' => $videoData['duration_hours'] ?? 0,
                                'filename' => $videoFile->getClientOriginalName(),
                                'file_size' => $videoFile->getSize(),
                                'mime_type' => $videoFile->getMimeType(),
                                'upload_status' => 'pending',
                                'temp_path' => $tempPath,
                                'video_path' => null,
                                'is_published' => false,
                            ]);

                            // Queue the upload
                            UploadVideoToFirebase::dispatch(
                                $videoLesson->id,
                                $tempPath,
                                $course->id
                            )->onQueue('video-uploads');

                            $queuedVideos++;
                            $keptVideoIds[] = $videoLesson->id;
                        }
                    }

                    // Delete videos that were not kept (and not explicitly marked for deletion)
                    $videosToDelete = array_diff($existingVideoIds, $keptVideoIds);
                    if (!empty($videosToDelete)) {
                        $deletedVideos = VideoLesson::whereIn('id', $videosToDelete)->get();
                        foreach ($deletedVideos as $video) {
                            if ($video->video_path) {
                                $firebaseService->deleteFile($video->video_path);
                            }
                            if ($video->temp_path && Storage::disk('local')->exists($video->temp_path)) {
                                Storage::disk('local')->delete($video->temp_path);
                            }
                            $video->delete();
                        }
                    }
                }
            }

            // Delete modules that were not kept (and not explicitly marked for deletion)
            $modulesToDelete = array_diff($existingModuleIds, $keptModuleIds);
            if (!empty($modulesToDelete)) {
                $modules = Module::whereIn('id', $modulesToDelete)->get();
                foreach ($modules as $mod) {
                    foreach ($mod->videoLessons as $video) {
                        if ($video->video_path) {
                            $firebaseService->deleteFile($video->video_path);
                        }
                        if ($video->temp_path && Storage::disk('local')->exists($video->temp_path)) {
                            Storage::disk('local')->delete($video->temp_path);
                        }
                    }
                    $mod->videoLessons()->delete();
                    $mod->delete();
                }
            }

            DB::commit();

            $message = 'Course updated successfully.';
            if ($queuedVideos > 0) {
                $message .= " {$queuedVideos} video(s) are being uploaded in the background.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'course' => $course->fresh(['modules.videoLessons']),
                    'modules_count' => count($updatedModules),
                    'videos_queued' => $queuedVideos,
                    'status' => $queuedVideos > 0 
                        ? 'Some videos are being processed. Check upload status endpoint.' 
                        : 'All changes applied immediately.'
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
            $firebaseService = app(FirebaseService::class);
            // Delete modules and their videos
            foreach ($course->modules as $module) {
                foreach ($module->videoLessons as $video) {
                    // Delete video file from storage
                    if ($video->video_path) {
                        $firebaseService->deleteFile($video->video_path);
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
