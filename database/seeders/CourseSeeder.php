<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $courses = array(
            array('id' => '1','teacher_id' => '1','subject_id' => '1','title' => 'Full Stack Web Development','description' => 'A complete course on building modern full stack web applications using Laravel and Vue.js.','thumbnail_url' => 'thumbnails/1763636134_691ef3a68a465.png','old_price' => '199.99','price' => '129.99','is_published' => '1','enrollment_count' => '1','processing_status' => 'pending','created_at' => '2025-11-20 10:55:34','updated_at' => '2025-11-20 17:48:40'),
            array('id' => '3','teacher_id' => '2','subject_id' => '13','title' => 'Dev Ops Mastering','description' => 'DevOps Mastering course video.','thumbnail_url' => NULL,'old_price' => '600.00','price' => '500.00','is_published' => '1','enrollment_count' => '1','processing_status' => 'pending','created_at' => '2025-11-21 04:07:41','updated_at' => '2025-11-21 04:34:06')
        );

        foreach ($courses as $course) {
            Course::create($course);
        }
    }
}
