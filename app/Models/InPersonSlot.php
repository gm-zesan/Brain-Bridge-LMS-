<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InPersonSlot extends Model
{
    protected $fillable = [
        'teacher_id',
        'subject_id',
        'title',
        'price',
        'description',
        'from_date',
        'to_date',
        'start_time',
        'end_time',
        'is_booked',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'is_booked' => 'boolean',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
