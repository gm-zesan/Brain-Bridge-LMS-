<?php

namespace App\Observers;

use App\Models\Review;
use App\Services\TeacherStatsService;

class ReviewObserver
{
    public function created(Review $review)
    {
        TeacherStatsService::updateTeacherStats($review->teacher_id, true);
    }
    
    public function saved(Review $review)
    {
        TeacherStatsService::updateTeacherStats($review->teacher_id, true);
    }

    public function deleted(Review $review)
    {
        TeacherStatsService::updateTeacherStats($review->teacher_id, true);
    }
}
