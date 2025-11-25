<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CourseEnrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'student_id',
        'teacher_id',
        'enrolled_at',
        'amount_paid',
        'currency',
        'payment_status',
        'payment_intent_id',
        'payment_method',
        'paid_at',
        'status',
        'progress_percentage',
        'completed_at',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'paid_at' => 'datetime',
        'completed_at' => 'datetime',
        'amount_paid' => 'decimal:2',
        'progress_percentage' => 'decimal:2',
    ];

    /**
     * Get the course that was enrolled
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the student who enrolled
     */
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the teacher of the course
     */
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }


    /**
     * Check if enrollment is paid
     */
    public function isPaid()
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Check if enrollment is free
     */
    public function isFree()
    {
        return $this->payment_status === 'free';
    }

    /**
     * Check if enrollment is active
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Check if course is completed
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Mark enrollment as completed
     */
    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'progress_percentage' => 100,
            'completed_at' => now(),
        ]);
    }

    /**
     * Update progress percentage
     */
    public function updateProgress($percentage)
    {
        $this->update([
            'progress_percentage' => min(100, max(0, $percentage))
        ]);

        // Auto-complete if 100%
        if ($percentage >= 100 && $this->status !== 'completed') {
            $this->markAsCompleted();
        }
    }

    /**
     * Scope to get active enrollments
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get completed enrollments
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get paid enrollments
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    /**
     * Scope to get free enrollments
     */
    public function scopeFree($query)
    {
        return $query->where('payment_status', 'free');
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute()
    {
        return '$' . number_format($this->amount_paid, 2);
    }

    /**
     * Get enrollment duration in days
     */
    public function getEnrollmentDurationAttribute()
    {
        return $this->enrolled_at->diffInDays(now());
    }
}
