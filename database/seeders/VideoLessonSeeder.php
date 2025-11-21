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
            array('id' => '1','module_id' => '1','title' => 'Getting Started with Web Development','description' => 'Overview of web basics, HTML, and CSS.','duration_hours' => '2','video_url' => NULL,'video_path' => 'videos/1763636134_691ef3a698c8e.mp4','file_size' => '1825811','mime_type' => 'video/mp4','filename' => '1763636134_691ef3a698c8e.mp4','is_published' => '1','created_at' => '2025-11-20 10:55:34','updated_at' => '2025-11-20 10:55:34'),
            array('id' => '2','module_id' => '1','title' => 'Understanding the Internet','description' => 'How browsers, servers, and protocols work.','duration_hours' => '1','video_url' => NULL,'video_path' => 'videos/1763636134_691ef3a6c4138.mp4','file_size' => '1825811','mime_type' => 'video/mp4','filename' => '1763636134_691ef3a6c4138.mp4','is_published' => '1','created_at' => '2025-11-20 10:55:34','updated_at' => '2025-11-20 10:55:34'),
            array('id' => '3','module_id' => '2','title' => 'Setting Up Laravel','description' => 'Installing and configuring Laravel project.','duration_hours' => '2','video_url' => NULL,'video_path' => 'videos/1763636134_691ef3a6eda11.mp4','file_size' => '1825811','mime_type' => 'video/mp4','filename' => '1763636134_691ef3a6eda11.mp4','is_published' => '1','created_at' => '2025-11-20 10:55:35','updated_at' => '2025-11-20 10:55:35'),
            array('id' => '5','module_id' => '4','title' => 'Introduction of Dev ops','description' => 'Introduction of Dev ops vedio','duration_hours' => '0','video_url' => NULL,'video_path' => 'videos/1763698061_691fe58dbed6d.mp4','file_size' => '1114353','mime_type' => 'video/mp4','filename' => '1763698061_691fe58dbed6d.mp4','is_published' => '1','created_at' => '2025-11-21 04:07:41','updated_at' => '2025-11-21 04:07:41')
        );

        foreach ($video_lessons as $lesson) {
            VideoLesson::create($lesson);
        }
    }
}
