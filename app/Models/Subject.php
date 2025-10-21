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
}
