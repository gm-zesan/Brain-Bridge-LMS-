<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Mail\SessionBookedMail;
use Illuminate\Http\Request;
use App\Models\AvailableSlot;
use App\Models\LessonSession;
use App\Services\MeetingService;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
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
    protected $paymentService;

    public function __construct(MeetingService $meetingService, PaymentService $paymentService)
    {
        $this->meetingService = $meetingService;
        $this->paymentService = $paymentService;
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
        
        // Filter by date - check if the date falls within the slot's date range
        if ($request->filled('date')) {
            $query->where('from_date', '<=', $request->date)
                ->where('to_date', '>=', $request->date);
        }
        
        // For students: only show unbooked slots
        if (Auth::user()->hasRole('student')) {
            $query->whereColumn('booked_count', '<', 'max_students');
        }
        
        $slots = $query->orderBy('from_date')
                    ->orderBy('start_time')
                    ->get()
                    ->map(function ($slot) {
                        return [
                            'id' => $slot->id,
                            'title' => $slot->title,
                            'teacher' => $slot->teacher,
                            'subject' => $slot->subject,
                            'from_date' => $slot->from_date,
                            'to_date' => $slot->to_date,
                            'time' => date('g:i A', strtotime($slot->start_time)) . ' - ' . date('g:i A', strtotime($slot->end_time)),
                            'available_seats' => $slot->max_students - $slot->booked_count,
                            'type' => $slot->type,
                            'price' => $slot->price,
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
        $slot = AvailableSlot::with('teacher:id,name,email', 'subject:id,name')->findOrFail($id);
        
        // Get all slots of the same teacher within the same date range
        $allSlotsSamePeriod = AvailableSlot::where('teacher_id', $slot->teacher_id)
            ->where('from_date', $slot->from_date)
            ->where('to_date', $slot->to_date)
            ->orderBy('start_time')
            ->get(['id', 'start_time', 'end_time', 'booked_count', 'max_students']);
        
        // Format time slots with availability
        $formattedSlots = $allSlotsSamePeriod->map(function ($s) {
            return [
                'id' => $s->id,
                'start_time' => date('H:i', strtotime($s->start_time)),
                'end_time' => date('H:i', strtotime($s->end_time)),
                'available_seats' => $s->max_students - $s->booked_count,
            ];
        });
        
        return response()->json([
            'id' => $slot->id,
            'title' => $slot->title,
            'teacher' => $slot->teacher,
            'subject' => $slot->subject,
            'subject_id' => $slot->subject_id,
            'from_date' => $slot->from_date,
            'to_date' => $slot->to_date,
            'slots' => $formattedSlots,
            'type' => $slot->type,
            'price' => $slot->price,
            'description' => $slot->description,
            'available_seats' => $slot->max_students - $slot->booked_count,
        ]);
    }

    
    
    public function bookSlot(Request $request)
    {
        $validated = $request->validate([
            'slot_id' => 'required|exists:available_slots,id',
        ]);

        DB::beginTransaction();
        try {
            // Lock the slot to prevent race conditions
            $slot = AvailableSlot::where('id', $validated['slot_id'])
                ->with('teacher', 'subject')
                ->lockForUpdate()
                ->firstOrFail();

            // Check availability
            if ($slot->booked_count >= $slot->max_students) {
                DB::rollBack();
                return response()->json(['message' => 'This slot is already full'], 400);
            }

            // Check duplicate booking
            if (LessonSession::where('slot_id', $slot->id)
                    ->where('student_id', Auth::id())
                    ->exists()) {
                DB::rollBack();
                return response()->json(['message' => 'You already booked this slot'], 400);
            }

            // Create lesson session
            $session = LessonSession::create([
                'slot_id' => $slot->id,
                'teacher_id' => $slot->teacher_id,
                'student_id' => Auth::id(),
                'subject_id' => $slot->subject_id,
                'scheduled_start_time' => $slot->start_time,
                'scheduled_end_time' => $slot->end_time,
                'session_type' => $slot->type,
                'status' => 'scheduled',
                'price' => $slot->price ?? 0,
            ]);

            // Create Google Meet
            $meeting = $this->meetingService->createGoogleMeet(
                $slot->teacher,
                Carbon::parse($slot->start_time),
                Carbon::parse($slot->end_time),
                'Lesson: ' . ($slot->subject->name ?? 'Session')
            );

            if ($meeting) {
                $session->update([
                    'meeting_platform' => $meeting['platform'],
                    'meeting_link' => $meeting['meeting_link'],
                    'meeting_id' => $meeting['meeting_id'],
                ]);
            }

            // Update slot
            $slot->increment('booked_count');
            if ($slot->booked_count >= $slot->max_students) {
                $slot->update(['is_booked' => true]);
            }

            DB::commit();

            // Send emails AFTER commit
            Mail::to($slot->teacher->email)->queue(new SessionBookedMail($session, 'teacher'));
            Mail::to(Auth::user()->email)->queue(new SessionBookedMail($session, 'student'));

            return response()->json([
                'success' => true,
                'message' => 'Slot booked successfully',
                'data' => [
                    'session_id' => $session->id,
                    'meeting_link' => $meeting['meeting_link'] ?? null,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Booking failed: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Booking failed. Please try again.'
            ], 500);
        }
    }


    /**
     * @OA\Post(
     *     path="/api/slot/bookings/intent",
     *     summary="Create booking payment intent",
     *     description="Creates a Stripe payment intent for booking a slot. Returns client_secret for frontend payment processing.",
     *     operationId="createBookingIntent",
     *     tags={"Available Slots"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Slot ID to book",
     *         @OA\JsonContent(
     *             required={"slot_id"},
     *             @OA\Property(
     *                 property="slot_id",
     *                 type="integer",
     *                 description="ID of the available slot to book",
     *                 example=1
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Booking intent created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="requires_payment", type="boolean", example=true),
     *             @OA\Property(property="client_secret", type="string", example="pi_xxxxx_secret_xxxxx"),
     *             @OA\Property(property="payment_intent_id", type="string", example="pi_xxxxx"),
     *             @OA\Property(property="amount", type="number", format="float", example=50.00),
     *             @OA\Property(
     *                 property="slot",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="subject", type="string", example="Mathematics"),
     *                 @OA\Property(property="teacher", type="string", example="John Doe"),
     *                 @OA\Property(property="start_time", type="string", format="date-time", example="2024-12-01 10:00:00"),
     *                 @OA\Property(property="end_time", type="string", format="date-time", example="2024-12-01 11:00:00"),
     *                 @OA\Property(property="price", type="number", format="float", example=50.00)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Slot is full or already booked",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="This slot is already full")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Slot not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Slot not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The slot id field is required."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="slot_id",
     *                     type="array",
     *                     @OA\Items(type="string", example="The slot id field is required.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error - payment intent creation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to create payment intent"),
     *             @OA\Property(property="error", type="string", example="Stripe API error message")
     *         )
     *     )
     * )
     */
    public function createBookingIntent(Request $request)
    {
        $validated = $request->validate([
            'slot_id' => 'required|exists:available_slots,id',
        ]);

        try {
            $slot = AvailableSlot::with('teacher', 'subject')
                ->findOrFail($validated['slot_id']);

            // Check availability (no lock needed yet, just checking)
            if ($slot->booked_count >= $slot->max_students) {
                return response()->json(['message' => 'This slot is already full'], 400);
            }

            // Check duplicate booking
            if (LessonSession::where('slot_id', $slot->id)
                    ->where('student_id', Auth::id())
                    ->exists()) {
                return response()->json(['message' => 'You already booked this slot'], 400);
            }

            // Check if price is zero (free session)
            if ($slot->price <= 0) {
                return response()->json([
                    'success' => true,
                    'requires_payment' => false,
                    'message' => 'This is a free session',
                ]);
            }

            // Create payment intent
            $paymentIntent = $this->paymentService->createPaymentIntent(
                $slot->price,
                'usd', // or get from config/slot
                [
                    'slot_id' => $slot->id,
                    'student_id' => Auth::id(),
                    'teacher_id' => $slot->teacher_id,
                    'subject' => $slot->subject->name ?? 'Session',
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
                'slot' => [
                    'id' => $slot->id,
                    'subject' => $slot->subject->name ?? 'Session',
                    'teacher' => $slot->teacher->name,
                    'start_time' => $slot->start_time,
                    'end_time' => $slot->end_time,
                    'price' => $slot->price,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Create booking intent failed: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Failed to create booking intent'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/slot/bookings/confirm",
     *     summary="Confirm booking after payment",
     *     description="Confirms and finalizes the booking after successful payment. Creates lesson session, generates Google Meet link, and sends confirmation emails. For free sessions, payment_intent_id is not required.",
     *     operationId="confirmBooking",
     *     tags={"Available Slots"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Booking confirmation data",
     *         @OA\JsonContent(
     *             required={"slot_id"},
     *             @OA\Property(
     *                 property="slot_id",
     *                 type="integer",
     *                 description="ID of the slot to confirm booking",
     *                 example=1
     *             ),
     *             @OA\Property(
     *                 property="payment_intent_id",
     *                 type="string",
     *                 description="Stripe payment intent ID (required for paid sessions, optional for free sessions)",
     *                 example="pi_3QXxxxxxxxxxxxx",
     *                 nullable=true
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Booking confirmed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Booking confirmed successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="session_id", type="integer", example=42),
     *                 @OA\Property(property="meeting_link", type="string", example="https://meet.google.com/abc-defg-hij", nullable=true),
     *                 @OA\Property(property="payment_status", type="string", example="paid", enum={"paid", "free"}),
     *                 @OA\Property(property="amount_paid", type="number", format="float", example=50.00)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - validation or payment error",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     @OA\Property(property="message", type="string", example="This slot is already full")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="message", type="string", example="You already booked this slot")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="message", type="string", example="Payment intent ID is required")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="message", type="string", example="Failed to verify payment")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="message", type="string", example="Payment not completed"),
     *                     @OA\Property(property="payment_status", type="string", example="requires_payment_method")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Slot not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Slot not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The slot id field is required."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="slot_id",
     *                     type="array",
     *                     @OA\Items(type="string", example="The slot id field is required.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error - booking confirmation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Booking confirmation failed. Please contact support.")
     *         )
     *     )
     * )
     */
    public function confirmBooking(Request $request)
    {
        $validated = $request->validate([
            'slot_id' => 'required|exists:available_slots,id',
            'payment_intent_id' => 'nullable|string', // nullable for free sessions
        ]);

        DB::beginTransaction();
        try {
            // Lock the slot to prevent race conditions
            $slot = AvailableSlot::where('id', $validated['slot_id'])
                ->with('teacher', 'subject')
                ->lockForUpdate()
                ->firstOrFail();

            // Re-check availability
            if ($slot->booked_count >= $slot->max_students) {
                DB::rollBack();
                return response()->json(['message' => 'This slot is already full'], 400);
            }

            // Re-check duplicate booking
            if (LessonSession::where('slot_id', $slot->id)
                    ->where('student_id', Auth::id())
                    ->exists()) {
                DB::rollBack();
                return response()->json(['message' => 'You already booked this slot'], 400);
            }

            // Verify payment if required
            $paymentStatus = 'free';
            $paymentIntentId = null;
            $amountPaid = 0;

            if ($slot->price > 0) {
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

            // Create lesson session
            $session = LessonSession::create([
                'slot_id' => $slot->id,
                'teacher_id' => $slot->teacher_id,
                'student_id' => Auth::id(),
                'subject_id' => $slot->subject_id,
                'scheduled_start_time' => $slot->start_time,
                'scheduled_end_time' => $slot->end_time,
                'session_type' => $slot->type,
                'status' => 'scheduled',
                'price' => $slot->price ?? 0,
                'payment_status' => $paymentStatus,
                'payment_intent_id' => $paymentIntentId,
                'payment_method' => $paymentIntentId ? 'stripe' : null,
                'amount_paid' => $amountPaid,
                'currency' => 'usd',
                'paid_at' => $paymentStatus === 'paid' ? now() : null,
            ]);

            // Create Google Meet
            $meeting = $this->meetingService->createGoogleMeet(
                $slot->teacher,
                Carbon::parse($slot->start_time),
                Carbon::parse($slot->end_time),
                'Lesson: ' . ($slot->subject->name ?? 'Session')
            );

            if ($meeting) {
                $session->update([
                    'meeting_platform' => $meeting['platform'],
                    'meeting_link' => $meeting['meeting_link'],
                    'meeting_id' => $meeting['meeting_id'],
                ]);
            }

            // Update slot
            $slot->increment('booked_count');
            if ($slot->booked_count >= $slot->max_students) {
                $slot->update(['is_booked' => true]);
            }

            DB::commit();

            // Send emails AFTER commit
            Mail::to($slot->teacher->email)->queue(new SessionBookedMail($session, 'teacher'));
            Mail::to(Auth::user()->email)->queue(new SessionBookedMail($session, 'student'));

            return response()->json([
                'success' => true,
                'message' => 'Booking confirmed successfully',
                'data' => [
                    'session_id' => $session->id,
                    'meeting_link' => $meeting['meeting_link'] ?? null,
                    'payment_status' => $paymentStatus,
                    'amount_paid' => $amountPaid,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Booking confirmation failed: ' . $e->getMessage(), [
                'slot_id' => $validated['slot_id'],
                'payment_intent_id' => $validated['payment_intent_id'] ?? null,
            ]);
            
            return response()->json([
                'message' => 'Booking confirmation failed. Please contact support.'
            ], 500);
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
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="subject_id", type="integer"),
     *             @OA\Property(property="from_date", type="string", format="date"),
     *             @OA\Property(property="to_date", type="string", format="date"),
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
     *                      @OA\Property(property="title", type="string"),
     *                      @OA\Property(property="teacher_id", type="integer"),
     *                      @OA\Property(property="subject_id", type="integer"),
     *                      @OA\Property(property="from_date", type="string", format="date"),
     *                      @OA\Property(property="to_date", type="string", format="date"),
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
            'title' => 'required|string',
            'subject_id' => 'required|exists:subjects,id',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'slots' => 'required|array|min:1',
            'slots.*.start_time' => 'required|date_format:H:i',
            'slots.*.end_time' => 'required|date_format:H:i|after:slots.*.start_time',
            'type' => 'required|in:one_to_one,group',
            'price' => 'nullable|numeric|min:0',
            'max_students' => 'required_if:type,group|integer|min:1',
            'description' => 'nullable|string',
        ]);


        $created = [];

        foreach ($validated['slots'] as $slot) {
            $created[] = AvailableSlot::create([
                'title' => $validated['title'],
                'teacher_id' => Auth::id(),
                'subject_id' => $validated['subject_id'],
                'from_date' => $validated['from_date'],
                'to_date' => $validated['to_date'],
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
     *     summary="Update an available slot",
     *     description="Teacher updates their own slot. Cannot update if the slot has bookings. All fields are optional.",
     *     operationId="updateSlot",
     *     tags={"Available Slots"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the slot to update",
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         description="Fields to update (all optional)",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="title",
     *                 type="string",
     *                 description="Slot title",
     *                 example="Advanced Mathematics Session"
     *             ),
     *             @OA\Property(
     *                 property="subject_id",
     *                 type="integer",
     *                 description="Subject ID",
     *                 example=1
     *             ),
     *             @OA\Property(
     *                 property="from_date",
     *                 type="string",
     *                 format="date",
     *                 description="Start date (YYYY-MM-DD)",
     *                 example="2024-12-01"
     *             ),
     *             @OA\Property(
     *                 property="to_date",
     *                 type="string",
     *                 format="date",
     *                 description="End date (YYYY-MM-DD), must be after or equal to from_date",
     *                 example="2024-12-31"
     *             ),
     *             @OA\Property(
     *                 property="start_time",
     *                 type="string",
     *                 format="time",
     *                 description="Start time in HH:MM format (24-hour)",
     *                 example="14:00"
     *             ),
     *             @OA\Property(
     *                 property="end_time",
     *                 type="string",
     *                 format="time",
     *                 description="End time in HH:MM format (24-hour), must be after start_time",
     *                 example="15:30"
     *             ),
     *             @OA\Property(
     *                 property="type",
     *                 type="string",
     *                 enum={"one_to_one", "group"},
     *                 description="Session type",
     *                 example="one_to_one"
     *             ),
     *             @OA\Property(
     *                 property="price",
     *                 type="number",
     *                 format="float",
     *                 description="Price per session (nullable, minimum 0)",
     *                 example=50.00,
     *                 nullable=true
     *             ),
     *             @OA\Property(
     *                 property="max_students",
     *                 type="integer",
     *                 description="Maximum number of students (minimum 1)",
     *                 example=5
     *             ),
     *             @OA\Property(
     *                 property="description",
     *                 type="string",
     *                 description="Slot description",
     *                 example="This session covers advanced calculus topics including derivatives and integrals.",
     *                 nullable=true
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Slot updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Slot updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="teacher_id", type="integer", example=10),
     *                 @OA\Property(property="title", type="string", example="Advanced Mathematics Session"),
     *                 @OA\Property(property="subject_id", type="integer", example=1),
     *                 @OA\Property(property="from_date", type="string", format="date", example="2024-12-01"),
     *                 @OA\Property(property="to_date", type="string", format="date", example="2024-12-31"),
     *                 @OA\Property(property="start_time", type="string", example="14:00:00"),
     *                 @OA\Property(property="end_time", type="string", example="15:30:00"),
     *                 @OA\Property(property="type", type="string", example="one_to_one"),
     *                 @OA\Property(property="price", type="number", format="float", example=50.00),
     *                 @OA\Property(property="max_students", type="integer", example=5),
     *                 @OA\Property(property="booked_count", type="integer", example=0),
     *                 @OA\Property(property="is_booked", type="boolean", example=false),
     *                 @OA\Property(property="description", type="string", example="Advanced calculus session"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-16T10:30:00.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-11-16T12:45:00.000000Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Cannot update - slot has bookings",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Cannot update a booked slot")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - not the slot owner",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Slot not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Slot not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The end time must be a time after start time."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="end_time",
     *                     type="array",
     *                     @OA\Items(type="string", example="The end time must be a time after start time.")
     *                 ),
     *                 @OA\Property(
     *                     property="to_date",
     *                     type="array",
     *                     @OA\Items(type="string", example="The to date must be a date after or equal to from date.")
     *                 ),
     *                 @OA\Property(
     *                     property="subject_id",
     *                     type="array",
     *                     @OA\Items(type="string", example="The selected subject id is invalid.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function update(Request $request, AvailableSlot $availableSlot)
    {
        if ($availableSlot->teacher_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($availableSlot->booked_count > 0) {
            return response()->json(['message' => 'Cannot update a booked slot'], 400);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string',
            'subject_id' => 'sometimes|exists:subjects,id',
            'from_date' => 'sometimes|date',
            'to_date' => 'sometimes|date|after_or_equal:from_date',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'type' => 'sometimes|in:one_to_one,group',
            'price' => 'nullable|numeric|min:0',
            'max_students' => 'required|integer|min:1',
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
        if ($availableSlot->teacher_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
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
        $query = AvailableSlot::where('teacher_id', Auth::id())
                ->with('subject')
                ->orderBy('from_date', 'asc')
                ->orderBy('start_time', 'asc');

        $slots = $query->paginate(10);

        return response()->json([
            'success' => true,
            'slots' => $slots
        ]);
    }



    /**
     * @OA\Get(
     *     path="teacher/slots/booked",
     *     summary="Teacher views their own booked slots",
     *     tags={"Available Slots"},
     *     @OA\Response(
     *         response=200,
     *         description="Teacher's slots",
     *     )
     * )
     */

    public function bookedSlots()
    {
        $slots = AvailableSlot::where('teacher_id', Auth::id())
            ->where('is_booked', true)
            ->with(['subject', 'session', 'session.student'])
            ->orderBy('from_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'slots' => $slots
        ]);
    }

}
