<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherLevel extends Model
{
    protected $fillable = [
        'level_name',
        'min_rating',
        'max_rating',
        'benefits',
    ];

    public function teachers()
    {
        return $this->hasMany(Teacher::class);
    }
}
