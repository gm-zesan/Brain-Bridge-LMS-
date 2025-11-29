<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseRequest extends Model
{
    protected $fillable = [
        'student_id',
        'course_name',
        'course_description',
        'subject',
        'additional_note',
        'status',
        'admin_note',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
