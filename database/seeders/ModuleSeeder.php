<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = array(
            array('id' => '1','course_id' => '1','title' => 'Introduction to Data Science','description' => 'Get an overview of the data science workflow, roles, and the types of problems data science can solve.','order_index' => '1','created_at' => '2025-11-10 07:10:06','updated_at' => '2025-11-10 07:10:06'),
            array('id' => '2','course_id' => '1','title' => 'Working with Data in Python','description' => 'Understand what data science is, where it is used, and what you will learn in this course.','order_index' => '2','created_at' => '2025-11-10 07:10:06','updated_at' => '2025-11-10 07:10:06'),
            array('id' => '4','course_id' => '3','title' => 'Introduction of web development','description' => 'Introduction of web developmentIntroduction of web developmentIntroduction of web development','order_index' => '1','created_at' => '2025-11-10 18:30:52','updated_at' => '2025-11-10 18:30:52')
        );

        foreach ($modules as $module) {
            Module::create($module);
        }
    }
}
