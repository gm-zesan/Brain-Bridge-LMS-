<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AvailableSlot;
use App\Models\Course;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/reviews",
     *     operationId="getReviews",
     *     tags={"Reviews"},
     *     summary="Get list of reviews",
     *     description="Returns reviews based on logged-in user role:
     *                  - Admin: all reviews
     *                  - Teacher: only reviews for themselves",
     *     @OA\Response(
     *         response=200,
     *         description="List of reviews",
     *         @OA\JsonContent(
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="rating", type="integer", example=5),
     *                     @OA\Property(property="comment", type="string", example="Great class!"),
     *                     @OA\Property(property="reviewer", type="object",
     *                         @OA\Property(property="id", type="integer", example=3),
     *                         @OA\Property(property="name", type="string", example="Student One")
     *                     ),
     *                     @OA\Property(property="teacher", type="object",
     *                         @OA\Property(property="id", type="integer", example=5),
     *                         @OA\Property(property="name", type="string", example="John Doe")
     *                     ),
     *                     @OA\Property(property="reviewable", type="object",
     *                         @OA\Property(property="id", type="integer", example=44),
     *                         @OA\Property(property="type", type="string", example="lesson_session")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="first_page_url", type="string", example="/api/reviews?page=1"),
     *             @OA\Property(property="last_page", type="integer", example=3),
     *             @OA\Property(property="last_page_url", type="string", example="/api/reviews?page=3"),
     *             @OA\Property(property="next_page_url", type="string", example="/api/reviews?page=2"),
     *             @OA\Property(property="prev_page_url", type="string", example=null),
     *             @OA\Property(property="per_page", type="integer", example=20),
     *             @OA\Property(property="total", type="integer", example=57)
     *         )
     *     ),
     *     @OA\Response(response=403, description="Role not recognized"),
     *     @OA\Response(response=401, description="Unauthorized"),
     * )
     */

    public function index()
    {
        $user = Auth::user();

        $query = Review::with([
            'reviewer:id,name',
            'teacher:id,name',
            'slot', 'course'
        ]);

        // Admin → See all reviews
        if ($user->hasRole('admin')) {
            return response()->json($query->latest()->paginate(20));
        }

        // Teacher → See only own reviews
        if ($user->hasRole('teacher')) {
            return response()->json(
                $query->where('teacher_id', $user->id)
                    ->latest()
                    ->paginate(20)
            );
        }

        // Default fallback (optional)
        return response()->json(['error' => 'Role not recognized'], 403);
    }


    /**
     * @OA\Post(
     *     path="/api/reviews/teacher",
     *     tags={"Reviews"},
     *     summary="Submit a review for a teacher",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"slot_id","rating"},
     *             @OA\Property(property="slot_id", type="integer", example=1),
     *             @OA\Property(property="rating", type="integer", example=5),
     *             @OA\Property(property="comment", type="string", example="Great teacher!")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Review submitted"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=409, description="Already reviewed"),
     * )
    */

    public function storeTeacherReview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'slot_id' => 'required|integer|exists:available_slots,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $student = Auth::user();
        $slot = AvailableSlot::find($request->slot_id);

        // Authorization checks
        if ($slot->teacher_id === $student->id) {
            return response()->json(['error' => 'You cannot review yourself'], 403);
        }

        $review = Review::create([
            'reviewer_id' => $student->id,
            'slot_id' => $slot->id,
            'teacher_id' => $slot->teacher_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json([
            'message' => 'Review submitted successfully',
            'review' => $review->load('teacher:id,name', 'slot:id,start_time'),
        ], 201);
    }


    /**
     * @OA\Post(
     *     path="/api/reviews/course",
     *     tags={"Reviews"},
     *     summary="Submit a review for a course",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"course_id","rating"},
     *             @OA\Property(property="course_id", type="integer", example=5),
     *             @OA\Property(property="rating", type="integer", example=4),
     *             @OA\Property(property="comment", type="string", example="Very informative course!")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Review submitted"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=409, description="Already reviewed"),
     * )
    */
    
    public function storeCourseReview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_id' => 'required|exists:courses,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $student = Auth::user();
        $course = Course::findOrFail($request->course_id);

        // Authorization check
        if ($course->teacher_id === $student->id) {
            return response()->json(['error' => 'You cannot review your own course'], 403);
        }

        $review = Review::create([
            'reviewer_id' => $student->id,
            'course_id' => $request->course_id,
            'teacher_id' => $course->teacher_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json([
            'message' => 'Course review submitted successfully',
            'review' => $review
        ], 201);
    }
}
