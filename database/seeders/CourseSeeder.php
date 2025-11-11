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
            array('id' => '1','teacher_id' => '4','subject_id' => '12','title' => 'Data Science with Python"','description' => 'From data cleaning to machine learning models','thumbnail_url' => 'thumbnails/1762758606_69118fce0e74a.jpg','old_price' => '600.00','price' => '500.00','is_published' => '1','created_at' => '2025-11-10 07:10:06','updated_at' => '2025-11-10 07:10:06'),
            array('id' => '3','teacher_id' => '4','subject_id' => '13','title' => 'Full Stack Web Development','description' => 'Full Stack Web Development in JavaScript','thumbnail_url' => 'thumbnails/1762799452_69122f5c83557.jpg','old_price' => '600.00','price' => '300.00','is_published' => '1','created_at' => '2025-11-10 18:30:52','updated_at' => '2025-11-10 18:30:52')
        );

        foreach ($courses as $course) {
            Course::create($course);
        }
    }
}
