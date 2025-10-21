<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    protected $fillable = [
        'name',
        'subject_id',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
