<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoLesson extends Model
{
    protected $fillable = [
        'module_id',
        'title',
        'description',
        'duration_hours',
        'video_url',
        'video_path',
        'filename',
        'is_published',
    ];

    public function module()
    {
        return $this->belongsTo(Module::class, 'module_id');
    }

}
