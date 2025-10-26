<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Earning extends Model
{
    protected $fillable = [
        'teacher_id',
        'lesson_session_id',
        'amount',
        'platform_fee',
        'status',
        'date',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function lessonSession()
    {
        return $this->belongsTo(LessonSession::class, 'lesson_session_id');
    }
}
