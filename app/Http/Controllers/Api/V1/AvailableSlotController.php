<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Mail\SessionBookedMail;
use Illuminate\Http\Request;
use App\Models\AvailableSlot;
use App\Models\LessonSession;
use App\Services\MeetingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

/**
 * @OA\Tag(
 *     name="Available Slots",
 *     description="API Endpoints for Available Slots and Booking"
 * )
 */
class AvailableSlotController extends Controller
{
    protected $meetingService;

    public function __construct(MeetingService $meetingService)
    {
        $this->meetingService = $meetingService;
    }
    /**
     * @OA\Get(
     *     path="/api/slots",
     *     summary="Get all available slots (filter by teacher/date)",
     *     tags={"Available Slots"},
     *     @OA\Parameter(
     *         name="teacher_id",
     *         in="query",
     *         description="Filter slots by teacher ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="date",
     *         in="query",
     *         description="Filter slots by date (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of available slots",
     *         @OA\JsonContent(
     *             @OA\Property(property="slots", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="teacher", type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string"),
     *                         @OA\Property(property="email", type="string")
     *                     ),
     *                     @OA\Property(property="subject", type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string")
     *                     ),
     *                     @OA\Property(property="date", type="string", format="date"),
     *                     @OA\Property(property="time", type="string"),
     *                     @OA\Property(property="available_seats", type="integer")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = AvailableSlot::with('teacher:id,name,email', 'subject:id,name');

        // Filter by teacher
        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        // Filter by date
        if ($request->filled('date')) {
            $query->where('available_date', $request->date);
        }

        // For students: only show unbooked slots
        if (Auth::user()->hasRole('student')) {
            $query->whereColumn('booked_count', '<', 'max_students');
        }

        $slots = $query->orderBy('available_date')
                    ->orderBy('start_time')
                    ->get()
                    ->map(function ($slot) {
                        return [
                            'id' => $slot->id,
                            'teacher' => $slot->teacher,
                            'subject' => $slot->subject,
                            'date' => $slot->available_date,
                            'time' => date('g:i A', strtotime($slot->start_time)) . ' - ' . date('g:i A', strtotime($slot->end_time)),
                            'available_seats' => $slot->max_students - $slot->booked_count,
                        ];
                    });

        return response()->json([
            'slots' => $slots
        ], 200);
    }


    /**
     * @OA\Get(
     *     path="/api/slots/{id}",
     *     summary="Get full details of a slot",
     *     tags={"Available Slots"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the slot",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Slot details",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="teacher", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string")
     *             ),
     *             @OA\Property(property="subject_id", type="integer"),
     *             @OA\Property(property="available_date", type="string", format="date"),
     *             @OA\Property(property="slots", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="start_time", type="string"),
     *                     @OA\Property(property="end_time", type="string")
     *                 )
     *             ),
     *             @OA\Property(property="type", type="string"),
     *             @OA\Property(property="price", type="number", format="float"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        // Find the slot with teacher & subject
        $slot = AvailableSlot::with('teacher:id,name,email', 'subject:id,name')
            ->findOrFail($id);

        // Get all slots of the same teacher on the same date
        $allSlotsSameDay = AvailableSlot::where('teacher_id', $slot->teacher_id)
            ->where('available_date', $slot->available_date)
            ->orderBy('start_time')
            ->get(['start_time', 'end_time']);

        // Format time nicely
        $formattedSlots = $allSlotsSameDay->map(function ($s) {
            return [
                'start_time' => date('H:i', strtotime($s->start_time)),
                'end_time' => date('H:i', strtotime($s->end_time)),
            ];
        });

        return response()->json([
            'id' => $slot->id,
            'teacher' => $slot->teacher,
            'subject_id' => $slot->subject_id,
            'available_date' => $slot->available_date,
            'slots' => $formattedSlots,
            'type' => $slot->type, // one_to_one / group
            'price' => $slot->price,
            'description' => $slot->description,
        ]);
    }

    
    /**
     * @OA\Post(
     *     path="/api/slots/book",
     *     summary="Student books a slot",
     *     tags={"Available Slots"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="slot_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Booking successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string", example="Slot booked successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="meeting_link", type="string", example="https://meet.google.com/abc-defg-hij")
     *             )
     *         )
     *     )
     * )
     */
    public function bookSlot(Request $request)
    {
        $validated = $request->validate([
            'slot_id' => 'required|exists:available_slots,id',
        ]);

        $slot = AvailableSlot::findOrFail($validated['slot_id']);
        $teacher = $slot->teacher;

        if ($slot->booked_count >= $slot->max_students) {
            return response()->json(['message' => 'This slot is already full'], 400);
        }

        // 1️⃣ Create meeting automatically
        $meeting = $this->meetingService->createGoogleMeet(
            $teacher, 
            Carbon::parse($slot->start_time),
            Carbon::parse($slot->end_time),
            'Lesson: '.$slot->subject->name ?? 'Session'
        );

        DB::beginTransaction();
        try {
            // Create lesson session
            $session = LessonSession::create([
                'slot_id' => $slot->id,
                'teacher_id' => $teacher->id,
                'student_id' => Auth::id(),
                'subject_id' => $slot->subject_id,
                'scheduled_start_time' => $slot->start_time,
                'scheduled_end_time' => $slot->end_time,
                'session_type' => $slot->type,
                'status' => 'scheduled',
                'price' => $slot->price ?? 0,
                'meeting_platform' => $meeting['platform'] ?? null,
                'meeting_link' => $meeting['meeting_link'] ?? null,
                'meeting_id' => $meeting['meeting_id'] ?? null,
            ]);

            $slot->increment('booked_count');

            if ($slot->booked_count >= $slot->max_students) {
                $slot->update(['is_booked' => true]);
            }

            Mail::to($teacher->email)->send(new SessionBookedMail($session, 'teacher'));
            Mail::to(Auth::user()->email)->send(new SessionBookedMail($session, 'student'));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Slot booked successfully',
                'data' => [
                    'meeting_link' => $meeting['meeting_link'] ?? null,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }


    /**
     * @OA\Post(
     *     path="/api/teacher/slots",
     *     summary="Teacher creates one or more slots",
     *     tags={"Available Slots"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="subject_id", type="integer"),
     *             @OA\Property(property="available_date", type="string", format="date"),
     *             @OA\Property(property="slots", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="start_time", type="string", example="09:00"),
     *                     @OA\Property(property="end_time", type="string", example="11:00")
     *                 )
     *             ),
     *             @OA\Property(property="type", type="string", enum={"one_to_one","group"}),
     *             @OA\Property(property="price", type="number"),
     *             @OA\Property(property="max_students", type="integer"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Slots created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(
     *                  property="data",
     *                  type="array",
     *                  @OA\Items(
     *                      type="object",
     *                      @OA\Property(property="id", type="integer"),
     *                      @OA\Property(property="teacher_id", type="integer"),
     *                      @OA\Property(property="subject_id", type="integer"),
     *                      @OA\Property(property="available_date", type="string", format="date"),
     *                      @OA\Property(property="start_time", type="string"),
     *                      @OA\Property(property="end_time", type="string"),
     *                      @OA\Property(property="type", type="string"),
     *                      @OA\Property(property="price", type="number"),
     *                      @OA\Property(property="description", type="string"),
     *                      @OA\Property(property="max_students", type="integer")
     *                  )
     *              )
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'available_date' => 'required|date',
            'slots' => 'required|array|min:1',
            'slots.*.start_time' => 'required|date_format:H:i',
            'slots.*.end_time' => 'required|date_format:H:i|after:slots.*.start_time',
            'type' => 'required|in:one_to_one,group',
            'price' => 'nullable|numeric|min:0',
            'max_students' => 'required_if:type,group|integer|min:2',
            'description' => 'nullable|string',
        ]);

        $created = [];

        foreach ($validated['slots'] as $slot) {
            $created[] = AvailableSlot::create([
                'teacher_id' => Auth::id(),
                'subject_id' => $validated['subject_id'],
                'available_date' => $validated['available_date'],
                'start_time' => $slot['start_time'],
                'end_time' => $slot['end_time'],
                'type' => $validated['type'],
                'price' => $validated['price'] ?? 0,
                'description' => $validated['description'] ?? null,
                'max_students' => $validated['type'] === 'group' ? $validated['max_students'] : 1,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Slots created successfully',
            'data' => $created
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/teacher/slots/{id}",
     *     summary="Teacher updates a slot",
     *     tags={"Available Slots"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="subject_id", type="integer"),
     *             @OA\Property(property="available_date", type="string", format="date"),
     *             @OA\Property(property="start_time", type="string"),
     *             @OA\Property(property="end_time", type="string"),
     *             @OA\Property(property="type", type="string", enum={"one_to_one","group"}),
     *             @OA\Property(property="price", type="number"),
     *             @OA\Property(property="max_students", type="integer"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Slot updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function update(Request $request, AvailableSlot $availableSlot)
    {
        if ($availableSlot->booked_count > 0) {
            return response()->json(['message' => 'Cannot update a booked slot'], 400);
        }

        $validated = $request->validate([
            'subject_id' => 'sometimes|exists:subjects,id',
            'available_date' => 'sometimes|date',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'type' => 'sometimes|in:one_to_one,group',
            'price' => 'nullable|numeric|min:0',
            'max_students' => 'required_if:type,group|integer|min:2',
            'description' => 'nullable|string',
        ]);

        $availableSlot->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Slot updated successfully',
            'data' => $availableSlot
        ]);
    }


    /**
     * @OA\Delete(
     *     path="/api/teacher/slots/{id}",
     *     summary="Teacher deletes a slot",
     *     tags={"Available Slots"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Slot deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(AvailableSlot $availableSlot)
    {
        if ($availableSlot->booked_count > 0) {
            return response()->json(['message' => 'Cannot delete a booked slot'], 400);
        }

        $availableSlot->delete();

        return response()->json([
            'success' => true,
            'message' => 'Slot deleted successfully'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/teacher/slots",
     *     summary="Teacher views their own slots",
     *     tags={"Available Slots"},
     *     @OA\Response(
     *         response=200,
     *         description="Teacher's slots",
     *     )
     * )
     */
    public function mySlots()
    {
        $slots = AvailableSlot::where('teacher_id', Auth::id())
            ->with('subject')
            ->orderByDesc('available_date')
            ->orderBy('start_time')
            ->get();

        return response()->json(['slots' => $slots]);
    }

}
