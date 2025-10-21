<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    protected $fillable = [
        'student_id',
        'teacher_id',
        'skill_id',
        'subject_id',
        'scheduled_start_time',
        'scheduled_end_time',
        'status',
        'price',
        'platform_session_id',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function skill()
    {
        return $this->belongsTo(Skill::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    
}
