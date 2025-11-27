<?php

namespace App\Services;

use App\Models\Teacher;
use App\Models\User;
use App\Notifications\TeacherPromoted;
use Illuminate\Support\Facades\DB;

class TeacherStatsService
{
    public static function updateTeacherStats(int $teacherId, bool $checkPromotion = false)
    {
        $teacher = User::find($teacherId)?->teacher;
        if (!$teacher) return;

        // Get all stats in a single optimized query
        $stats = DB::table('reviews')
            ->where('teacher_id', $teacherId)
            ->selectRaw('
                ROUND(AVG(rating), 2) as average_rating,
                COUNT(CASE WHEN rating = 5 THEN 1 END) as five_star_reviews
            ')
            ->first();

        // Calculate streak separately (requires ordered data)
        $recentReviews = DB::table('reviews')
            ->where('teacher_id', $teacherId)
            ->orderByDesc('created_at')
            ->pluck('rating');

        $streak = 0;
        foreach ($recentReviews as $rating) {
            if ($rating >= 4) $streak++;
            else break;
        }
        
        // Get rebook count
        $rebookCount = DB::table('available_slots')
            ->where('teacher_id', $teacherId)
            ->where('rebooked', true)
            ->count();

        // Update teacher stats
        $teacher->update([
            'average_rating' => $stats->average_rating ?? 0,
            'five_star_reviews' => $stats->five_star_reviews ?? 0,
            'streak_good_sessions' => $streak,
            'rebook_count' => $rebookCount,
        ]);

        // Check promotion instantly if flag true
        if ($checkPromotion) {
            self::evaluatePromotion($teacher);
        }
    }


    private static function evaluatePromotion(Teacher $teacher)
    {
        $currentLevel = $teacher->teacher_level_id;
        $newLevel = self::determineLevel($teacher);

        if ($newLevel <= $currentLevel) return;

        $newBasePay = self::calculateBasePay($teacher->base_pay, $currentLevel, $newLevel);

        $teacher->update([
            'teacher_level_id' => $newLevel,
            'base_pay' => $newBasePay,
        ]);

        $teacher->load('teacherLevel');

        $teacher->user->notify(
            new TeacherPromoted($teacher->teacherLevel->level_name, $newBasePay)
        );
    }

    private static function determineLevel(Teacher $teacher): int
    {
        $rating = $teacher->average_rating ?? 0;
        $sessions = $teacher->total_sessions ?? 0;
        $cancellations = $teacher->cancelled_sessions ?? 0;
        $rebooks = $teacher->rebook_count ?? 0;
        $fiveStars = $teacher->five_star_reviews ?? 0;
        $streak = $teacher->streak_good_sessions ?? 0;

        if ($rating >= 4.7 && $sessions >= 50 && $cancellations == 0) return 5;
        if ($rating >= 4.6 && $sessions >= 30 && $rebooks >= 10) return 4;
        if ($rating >= 4.5 && $fiveStars >= 15 && $streak >= 10) return 3;
        if ($rating >= 4.3 && $sessions >= 5) return 2;

        return 1;
    }

    private static function calculateBasePay(float $currentBasePay, int $oldLevel, int $newLevel): float
    {
        $originalBasePay = self::getOriginalBasePay($currentBasePay, $oldLevel);

        $multiplier = match($newLevel) {
            1 => 1.0,
            2 => 1.10,
            3 => 1.20,
            4 => 1.35,
            5 => 1.50,
            default => 1.0
        };

        return round($originalBasePay * $multiplier, 2);
    }

    private static function getOriginalBasePay(float $currentBasePay, int $currentLevel): float
    {
        $currentMultiplier = match($currentLevel) {
            1 => 1.0,
            2 => 1.10,
            3 => 1.20,
            4 => 1.35,
            5 => 1.50,
            default => 1.0
        };

        return $currentBasePay / $currentMultiplier;
    }
}
