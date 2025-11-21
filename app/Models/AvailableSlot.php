<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvailableSlot extends Model
{
    protected $fillable = [
        'teacher_id',
        'subject_id',
        'title',
        'type',
        'price',
        'description',
        'from_date',
        'to_date',
        'start_time',
        'end_time',
        'meeting_link',
        'is_booked',
        'max_students',
        'booked_count',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'is_booked' => 'boolean',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function session()
    {
        return $this->hasOne(LessonSession::class, 'slot_id');
    }


}
