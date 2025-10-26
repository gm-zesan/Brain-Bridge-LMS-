<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'video_lesson_id',
        'lesson_session_id',
        'amount',
        'currency',
        'type',
        'provider',
        'provider_reference',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function videoLesson()
    {
        return $this->belongsTo(VideoLesson::class);
    }

    public function lessonSession()
    {
        return $this->belongsTo(LessonSession::class);
    }
}
