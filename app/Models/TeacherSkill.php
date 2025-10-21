<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherSkill extends Model
{
    protected $fillable = [
        'teacher_id',
        'skill_id',
        'years_of_experience',
        'hourly_rate',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function skill()
    {
        return $this->belongsTo(Skill::class);
    }
}
