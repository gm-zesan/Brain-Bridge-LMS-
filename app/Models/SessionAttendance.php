<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SessionAttendance extends Model
{
    protected $fillable = [
        'session_id',
        'started_at',
        'ended_at',
        'student_joined',
        'teacher_joined',
        'notes',
    ];

    public function session()
    {
        return $this->belongsTo(Session::class);
    }
}
