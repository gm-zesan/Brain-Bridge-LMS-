<?php

namespace App\Services;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TeacherStatsService
{
    public static function updateTeacherStats(int $teacherId)
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
            if ($rating >= 4) {
                $streak++;
            } else {
                break;
            }
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
    }
}
