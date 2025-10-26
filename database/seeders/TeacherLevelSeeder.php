<?php

namespace Database\Seeders;

use App\Models\TeacherLevel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TeacherLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed default teacher levels
        $levels = [
            [
                'level_name' => 'Bronze',
                'min_rating' => 0,
                'max_rating' => 999,
                'benefits' => 'Basic support and resources.',
            ],
            [
                'level_name' => 'Silver',
                'min_rating' => 1000,
                'max_rating' => 1999,
                'benefits' => 'Priority support and access to advanced resources.',
            ],
            [
                'level_name' => 'Gold',
                'min_rating' => 2000,
                'max_rating' => 2999,
                'benefits' => 'Dedicated support, premium resources, and promotional opportunities.',
            ],
            [
                'level_name' => 'Platinum',
                'min_rating' => 3000,
                'max_rating' => 3999,
                'benefits' => 'All Gold benefits plus exclusive event invitations and higher revenue share.',
            ],
            [
                'level_name' => 'Master',
                'min_rating' => 4000,
                'max_rating' => 5000,
                'benefits' => 'All Platinum benefits plus personal coaching and top-tier promotional features.',
            ],
        ];

        foreach ($levels as $level) {
            TeacherLevel::create($level);
        }
    }
}
