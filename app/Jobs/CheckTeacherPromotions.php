<?php

namespace App\Jobs;

use App\Models\Teacher;
use App\Notifications\TeacherPromoted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Bus\Dispatchable;

class CheckTeacherPromotions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        Teacher::with('teacherLevel', 'user')->chunk(100, function($teachers) {
            foreach ($teachers as $t) {
                $newLevel = $this->determineLevel($t);

                if ($newLevel > $t->teacher_level_id) {
                    $t->teacher_level_id = $newLevel;
                    $t->save();
                    $t->refresh();

                    Notification::send($t->user, new TeacherPromoted($t->teacherLevel->level_name));
                }
            }
        });
    }

    private function determineLevel($t)
    {
        if ($t->average_rating >= 4.7 && $t->total_sessions >= 50 && $t->cancelled_sessions == 0) return 5;
        if ($t->average_rating >= 4.6 && $t->total_sessions >= 30 && $t->rebook_count >= 10) return 4;
        if ($t->average_rating >= 4.5 && $t->five_star_reviews >= 15 && $t->streak_good_sessions >= 10) return 3;
        if ($t->average_rating >= 4.3 && $t->total_sessions >= 5) return 2;
        return 1;
    }
}
