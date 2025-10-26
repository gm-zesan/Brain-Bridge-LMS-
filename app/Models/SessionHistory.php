<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SessionHistory extends Model
{
    protected $fillable = [
        'lesson_session_id',
        'started_at',
        'ended_at',
        'is_student_joined',
        'is_teacher_joined',
        'notes',
    ];

    public function lessonSession()
    {
        return $this->belongsTo(LessonSession::class);
    }
    
}
