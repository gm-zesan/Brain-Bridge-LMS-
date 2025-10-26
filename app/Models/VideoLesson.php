<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoLesson extends Model
{
    protected $fillable = [
        'teacher_id',
        'title',
        'description',
        'price',
        'duration_hours',
        'video_url',
        'is_published',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
