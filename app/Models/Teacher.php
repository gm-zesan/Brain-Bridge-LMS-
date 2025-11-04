<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    protected $fillable = [
        'user_id',
        'teacher_level_id',
        'title',
        'introduction_video',
        'base_pay',
        'total_sessions',
        'average_rating',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function teacherLevel()
    {
        return $this->belongsTo(TeacherLevel::class);
    }

    public function videoLessons()
    {
        return $this->hasMany(VideoLesson::class);
    }

    public function skills()
    {
        return $this->hasMany(TeacherSkill::class);
    }

}
