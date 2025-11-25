<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherSkill extends Model
{
    protected $fillable = [
        'teacher_id',
        'skill_id',
        'years_of_experience',
    ];

    protected $casts = [
        'years_of_experience' => 'float',
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
