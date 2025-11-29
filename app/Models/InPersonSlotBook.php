<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InPersonSlotBook extends Model
{
    protected $fillable = [
        'slot_id',
        'student_id',
        'teacher_id',
        'subject_id',
        'scheduled_date',
        'scheduled_start_time',
        'scheduled_end_time',
        'status',
        'price',
        'payment_status',
        'payment_intent_id',
        'payment_method',
        'amount_paid',
        'currency',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function slot()
    {
        return $this->belongsTo(InPersonSlot::class, 'slot_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
