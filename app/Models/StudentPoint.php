<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentPoint extends Model
{
    protected $fillable = [
        'student_id',
        'points',
        'reason',
        'related_session_id',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function lessonSession()
    {
        return $this->belongsTo(LessonSession::class, 'related_session_id');
    }
}
