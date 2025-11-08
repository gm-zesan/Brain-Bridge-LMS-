<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LessonSession;
use App\Models\AvailableSlot;
use App\Services\MeetingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\SessionBookedMail;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class LessonSessionController extends Controller
{
    protected $meetingService;

    public function __construct(MeetingService $meetingService)
    {
        $this->meetingService = $meetingService;
    }

    public function store(Request $request)
    {
        $request->validate([
            'slot_id' => 'required|exists:available_slots,id',
            'subject_id' => 'required|exists:subjects,id',
        ]);

        $slot = AvailableSlot::findOrFail($request->slot_id);
        $teacher = $slot->teacher;

        // 1️⃣ Create meeting automatically
        $meeting = $this->meetingService->createGoogleMeet(
            $teacher, 
            Carbon::parse($slot->start_time),
            Carbon::parse($slot->end_time),
            'Lesson: '.$slot->subject->name ?? 'Session'
        );
        

        // 2️⃣ Save session record
        $session = LessonSession::create([
            'slot_id' => $slot->id,
            'student_id' => Auth::id(),
            'teacher_id' => $teacher->id,
            'subject_id' => $request->subject_id,
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

        // Mark slot as booked
        $slot->update(['is_booked' => true]);

        // 3️⃣ Send Email Notifications
        Mail::to($teacher->email)->send(new SessionBookedMail($session, 'teacher'));
        Mail::to(Auth::user()->email)->send(new SessionBookedMail($session, 'student'));

        return response()->json([
            'message' => 'Session booked successfully.',
            'data' => [
                'meeting_link' => $meeting['meeting_link'] ?? null,
            ]
        ]);
    }
}
