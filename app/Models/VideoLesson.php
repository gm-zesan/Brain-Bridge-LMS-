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
        'video_path',
        'filename',
        'file_size',
        'mime_type',
        'is_published',
    ];

    public function module()
    {
        return $this->belongsTo(Module::class, 'module_id');
    }

}
