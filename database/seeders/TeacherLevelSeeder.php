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
            ['id' => 1, 'level_name' => 'Bronze', 'min_rating' => 0, 'benefits' => 'Base Pay'],
            ['id' => 2, 'level_name' => 'Silver', 'min_rating' => 4.3, 'benefits' => '+10% Pay'],
            ['id' => 3, 'level_name' => 'Gold', 'min_rating' => 4.5, 'benefits' => '+20% Pay'],
            ['id' => 4, 'level_name' => 'Platinum', 'min_rating' => 4.6, 'benefits' => '+35% Pay'],
            ['id' => 5, 'level_name' => 'Master', 'min_rating' => 4.7, 'benefits' => 'Custom Pay'],
        ];

        foreach ($levels as $level) {
            TeacherLevel::create($level);
        }
    }
}
