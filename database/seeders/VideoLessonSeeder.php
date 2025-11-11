<?php

namespace Database\Seeders;

use App\Models\VideoLesson;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VideoLessonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $video_lessons = array(
            array('id' => '1','module_id' => '1','title' => 'What is Data Science?','description' => 'Understand what data science is, where it is used, and what you will learn in this course.','duration_hours' => '0','video_url' => 'videos/1762758606_69118fce19054.mp4','video_path' => 'public/videos/1762758606_69118fce19054.mp4','filename' => '1762758606_69118fce19054.mp4','is_published' => '1','created_at' => '2025-11-10 07:10:06','updated_at' => '2025-11-10 07:10:06'),
            array('id' => '2','module_id' => '2','title' => 'Pandas Basics','description' => 'Pandas Basics-description.','duration_hours' => '0','video_url' => 'videos/1762758606_69118fce1da55.mp4','video_path' => 'public/videos/1762758606_69118fce1da55.mp4','filename' => '1762758606_69118fce1da55.mp4','is_published' => '1','created_at' => '2025-11-10 07:10:06','updated_at' => '2025-11-10 07:10:06'),
            array('id' => '4','module_id' => '4','title' => 'What is web development','description' => 'Introduction of web development, Introduction of web development','duration_hours' => '0','video_url' => 'videos/1762799452_69122f5c86d39.mp4','video_path' => 'videos/1762799452_69122f5c86d39.mp4','filename' => '1762799452_69122f5c86d39.mp4','is_published' => '1','created_at' => '2025-11-10 18:30:52','updated_at' => '2025-11-10 18:30:52')
        );

        foreach ($video_lessons as $lesson) {
            VideoLesson::create($lesson);
        }
    }
}
