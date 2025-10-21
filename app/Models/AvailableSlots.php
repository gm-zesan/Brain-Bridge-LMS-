<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvailableSlots extends Model
{
    protected $fillable = [
        'teacher_id',
        'start_time',
        'end_time',
        'recurrence_rule',
        'is_booked',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
