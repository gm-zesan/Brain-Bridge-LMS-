<?php

namespace App\Jobs;

use App\Models\Teacher;
use App\Notifications\TeacherPromoted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;

class CheckTeacherPromotions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Teacher::with('teacherLevel', 'user')->whereNotNull('user_id')->chunk(100, function($teachers) {
            foreach ($teachers as $teacher) {
                $this->evaluateTeacher($teacher);
            }
        });
    }

    private function evaluateTeacher(Teacher $teacher): void
    {
        $currentLevel = $teacher->teacher_level_id;
        $newLevel = $this->determineLevel($teacher);

        // Only allow promotions, no demotion
        if ($newLevel <= $currentLevel) {
            return;
        }

        // Calculate new base pay
        $newBasePay = $this->calculateBasePay($teacher->base_pay, $currentLevel, $newLevel);

        // Update level and base pay
        $teacher->update([
            'teacher_level_id' => $newLevel,
            'base_pay' => $newBasePay
        ]);
        $teacher->load('teacherLevel');

        $teacher->user->notify(
            new TeacherPromoted($teacher->teacherLevel->level_name, $newBasePay)
        );
    }

    private function determineLevel(Teacher $teacher): int
    {
        $rating = $teacher->average_rating ?? 0;
        $sessions = $teacher->total_sessions ?? 0;
        $cancellations = $teacher->cancelled_sessions ?? 0;
        $rebooks = $teacher->rebook_count ?? 0;
        $fiveStars = $teacher->five_star_reviews ?? 0;
        $streak = $teacher->streak_good_sessions ?? 0;

        // Level 5: Master
        if ($rating >= 4.7 && $sessions >= 50 && $cancellations == 0) {
            return 5;
        }

        // Level 4: Platinum
        if ($rating >= 4.6 && $sessions >= 30 && $rebooks >= 10) {
            return 4;
        }

        // Level 3: Gold
        if ($rating >= 4.5 && $fiveStars >= 15 && $streak >= 10) {
            return 3;
        }

        // Level 2: Silver
        if ($rating >= 4.3 && $sessions >= 5) {
            return 2;
        }

        // Level 1: Bronze
        return 1;
    }


    private function calculateBasePay(float $currentBasePay, int $oldLevel, int $newLevel): float
    {
        $originalBasePay = $this->getOriginalBasePay($currentBasePay, $oldLevel);

        $multiplier = match($newLevel) {
            1 => 1.0,    // Bronze: Base Pay (100%)
            2 => 1.10,   // Silver: +10%
            3 => 1.20,   // Gold: +20%
            4 => 1.35,   // Platinum: +35%
            5 => 1.50,   // Master: Custom Pay (+50% as example, adjust as needed)
            default => 1.0
        };

        return round($originalBasePay * $multiplier, 2);
    }

    private function getOriginalBasePay(float $currentBasePay, int $currentLevel): float
    {
        $currentMultiplier = match($currentLevel) {
            1 => 1.0,    // Bronze
            2 => 1.10,   // Silver
            3 => 1.20,   // Gold
            4 => 1.35,   // Platinum
            5 => 1.50,   // Master
            default => 1.0
        };

        return $currentBasePay / $currentMultiplier;
    }
}
