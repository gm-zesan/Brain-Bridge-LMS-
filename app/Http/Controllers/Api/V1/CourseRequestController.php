<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CourseRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Course Requests",
 *     description="API for handling course request submissions, listing, and approvals"
 * )
 */
class CourseRequestController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/course-requests",
     *     summary="Get all course requests (Admin)",
     *     description="Returns a list of all course requests submitted by students.",
     *     tags={"Course Requests"},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of all course requests",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="student_id", type="integer", example=10),
     *                 @OA\Property(property="course_name", type="string", example="Advanced Machine Learning"),
     *                 @OA\Property(property="course_description", type="string", example="A deep learning focused course"),
     *                 @OA\Property(property="subject", type="string", example="AI / ML"),
     *                 @OA\Property(property="additional_note", type="string", example="This course is important for my research."),
     *
     *                 @OA\Property(property="status", type="string", example="pending"),
     *                 @OA\Property(property="admin_note", type="string", example=""),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-11-29T05:41:51Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-11-29T05:41:51Z"),
     *
     *                 @OA\Property(
     *                     property="student",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=10),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john.doe@example.com")
     *                 )
     *             )
     *         )
     *     )
     * )
    */

    public function index()
    {
        $requests = CourseRequest::with('student')->orderBy('id', 'desc')->paginate(20);
        return response()->json($requests);
    }


    /**
     * @OA\Get(
     *     path="/api/my-course-requests",
     *     summary="Get logged-in student's course requests",
     *     description="Returns a list of course requests submitted by the authenticated student.",
     *     tags={"Course Requests"},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Student's course request list",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="My Course Requests"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="course_name", type="string", example="Advanced AI"),
     *                     @OA\Property(property="course_description", type="string", example="A deep learning focused course"),
     *                     @OA\Property(property="subject", type="string", example="AI / ML"),
     *                     @OA\Property(property="additional_note", type="string", example="Useful for project work"),
     *                     @OA\Property(property="status", type="string", example="pending"),
     *                     @OA\Property(property="admin_note", type="string", example=""),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-11-29T05:41:51Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-11-29T05:41:51Z")
     *                 )
     *             )
     *         )
     *     )
     * )
    */


    public function myRequests()
    {
        $requests = CourseRequest::where('student_id', Auth::id())
            ->orderBy('id', 'desc')
            ->paginate(10);

        return response()->json([
            'message' => 'My Course Requests',
            'data' => $requests
        ]);
    }


    /**
     * @OA\Post(
     *     path="/api/course-requests",
     *     summary="Submit a new course request (Student)",
     *     description="Allows a student to submit a new course request for a course that does not exist on the platform.",
     *     tags={"Course Requests"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="course_name", type="string", example="Advanced Machine Learning"),
     *             @OA\Property(property="course_description", type="string", example="A deep learning focused course"),
     *             @OA\Property(property="subject", type="string", example="AI / ML"),
     *             @OA\Property(property="additional_note", type="string", example="This course is important for my research.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Course request submitted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Course request submitted successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="course_name", type="string", example="Advanced Machine Learning"),
     *                 @OA\Property(property="course_description", type="string", example="A deep learning focused course"),
     *                 @OA\Property(property="subject", type="string", example="AI / ML"),
     *                 @OA\Property(property="additional_note", type="string", example="This course is important for my research.")
     *             )
     *         )
     *     )
     * )
    */

    public function store(Request $request)
    {
        $request->validate([
            'course_name' => 'required|string|max:255',
            'course_description' => 'nullable|string',
            'subject' => 'nullable|string',
            'additional_note' => 'nullable|string',
        ]);

        $courseRequest = CourseRequest::create([
            'student_id' => Auth::id(),
            'course_name' => $request->course_name,
            'course_description' => $request->course_description,
            'subject' => $request->subject,
            'additional_note' => $request->additional_note,
        ]);

        return response()->json([
            'message' => 'Course request submitted successfully.',
            'data' => $courseRequest
        ], 201);
    }

    


    /**
     * @OA\Post(
     *     path="/api/course-request/{id}/approve",
     *     summary="Approve a course request (Admin)",
     *     description="Admin approves a pending course request.",
     *     tags={"Course Requests"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Course request ID",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="admin_note", type="string", example="Approved and will be added next month")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Course request approved",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Course request approved")
     *         )
     *     )
     * )
    */
    public function approve($id, Request $request)
    {
        $requestData = CourseRequest::findOrFail($id);

        $requestData->update([
            'status' => 'approved',
            'admin_note' => $request->admin_note,
        ]);

        return response()->json(['message' => 'Course request approved']);
    }

    
    /**
     * @OA\Post(
     *     path="/api/course-request/{id}/reject",
     *     summary="Reject a course request (Admin)",
     *     description="Admin rejects a course request.",
     *     tags={"Course Requests"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Course request ID",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="admin_note", type="string", example="Not suitable for our platform")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Course request rejected",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Course request rejected")
     *         )
     *     )
     * )
    */
    public function reject($id, Request $request)
    {
        $requestData = CourseRequest::findOrFail($id);

        $requestData->update([
            'status' => 'rejected',
            'admin_note' => $request->admin_note,
        ]);

        return response()->json(['message' => 'Course request rejected']);
    }


    /**
     * @OA\Delete(
     *     path="/api/my-course-requests-delete/{id}",
     *     summary="Delete a course request (Student)",
     *     description="Allows a student to delete their own course request.",
     *     tags={"Course Requests"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Course request ID",
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Course request deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Course request deleted")
     *         )
     *     )
     * )
    */
    public function myRequestsDestroy($id)
    {
        $requestData = CourseRequest::findOrFail($id);
        $requestData->delete();

        return response()->json(['message' => 'Course request deleted']);
    }
}
