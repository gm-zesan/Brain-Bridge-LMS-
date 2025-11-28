<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Notifications",
 *     description="API Endpoints for user notifications"
 * )
 */
class NotificationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/notifications",
     *     tags={"Notifications"},
     *     summary="Get paginated notifications for authenticated user (20 per page)",
     *     @OA\Response(
     *         response=200,
     *         description="List of notifications",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="notifications", type="object")
     *         )
     *     )
     * )
    */
    public function index()
    {
        $user = Auth::user();

        $notifications = $user->notifications()->latest()->paginate(20);

        return response()->json([
            'status' => 'success',
            'notifications' => $notifications
        ]);
    }


    /**
     * @OA\Get(
     *     path="/api/notifications/latest",
     *     tags={"Notifications"},
     *     summary="Get the latest notification for authenticated user",
     *     @OA\Response(
     *         response=200,
     *         description="Latest notification",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(
     *                 property="notification",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="c1d4a8d1-8dc2-4b29-a7af-9134e3f03e49"),
     *                 @OA\Property(property="type", type="string", example="App\\Notifications\\TeacherPromoted"),
     *                 @OA\Property(property="data", type="object"),
     *                 @OA\Property(property="read_at", type="string", nullable=true, example="2025-11-26 13:00:00"),
     *                 @OA\Property(property="created_at", type="string", example="2025-11-26 12:55:00")
     *             )
     *         )
     *     )
     * )
*/

    public function latest()
    {
        $user = Auth::user();

        $notifications = $user->notifications()->latest()->take(10)->get();

        return response()->json([
            'status' => 'success',
            'notifications' => $notifications
        ]);
    }


    /**
     * @OA\Post(
     *     path="/api/notifications/mark-read/{id}",
     *     tags={"Notifications"},
     *     summary="Mark a notification as read",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Notification ID",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notification marked as read",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success")
     *         )
     *     )
     * )
    */
    public function markRead($id)
    {
        $user = Auth::user();

        $notification = $user->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json(['status' => 'success']);
    }


    /**
     * @OA\Post(
     *     path="/api/v1/notifications/mark-all-read",
     *     tags={"Notifications"},
     *     summary="Mark all notifications as read",
     *     @OA\Response(
     *         response=200,
     *         description="All notifications marked as read",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success")
     *         )
     *     )
     * )
    */
    public function markAllRead()
    {
        $user = Auth::user();
        $user->unreadNotifications->markAsRead();

        return response()->json(['status' => 'success']);
    }
}
