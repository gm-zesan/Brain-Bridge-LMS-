<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = ['course_id', 'title', 'description', 'order_index'];

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function videoLessons()
    {
        return $this->hasMany(VideoLesson::class, 'module_id');
    }
}
