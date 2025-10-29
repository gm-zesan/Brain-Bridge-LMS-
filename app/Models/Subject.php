<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = [
        'name',
        'parent_id',
    ];

    public function parent()
    {
        return $this->belongsTo(Subject::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Subject::class, 'parent_id');
    }

    public function skills()
    {
        return $this->hasMany(Skill::class);
    }

    public function videoLessons()
    {
        return $this->hasMany(VideoLesson::class);
    }
}
