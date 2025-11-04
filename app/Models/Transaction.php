<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
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

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function lessonSession()
    {
        return $this->belongsTo(LessonSession::class);
    }
}
