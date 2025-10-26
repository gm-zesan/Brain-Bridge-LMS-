<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiMatches extends Model
{
    protected $fillable = [
        'student_id',
        'teacher_id',
        'match_score',
        'reason',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    
}
