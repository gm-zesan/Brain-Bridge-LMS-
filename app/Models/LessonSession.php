<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonSession extends Model
{
    protected $fillable = [
        'slot_id',
        'student_id',
        'teacher_id',
        'subject_id',
        'scheduled_date',
        'scheduled_start_time',
        'scheduled_end_time',
        'session_type',
        'status',
        'price',
        'meeting_platform',
        'meeting_link',
        'meeting_id',
        'description'
    ];

    public function slot()
    {
        return $this->belongsTo(AvailableSlot::class, 'slot_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function history()
    {
        return $this->hasOne(SessionHistory::class, 'lesson_session_id');
    }
}
