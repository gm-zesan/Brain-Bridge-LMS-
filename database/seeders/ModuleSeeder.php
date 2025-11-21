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
            array('id' => '1','course_id' => '1','title' => 'Introduction to Web Development','description' => 'Learn about web technologies and development tools.','order_index' => '1','created_at' => '2025-11-20 10:55:34','updated_at' => '2025-11-20 10:55:34'),
            array('id' => '2','course_id' => '1','title' => 'Backend with Laravel','description' => 'Deep dive into Laravel framework for backend development.','order_index' => '2','created_at' => '2025-11-20 10:55:34','updated_at' => '2025-11-20 10:55:34'),
            array('id' => '4','course_id' => '3','title' => 'Introduction of Dev ops','description' => 'Introduction of Dev ops vedio','order_index' => '1','created_at' => '2025-11-21 04:07:41','updated_at' => '2025-11-21 04:07:41')
        );

        foreach ($modules as $module) {
            Module::create($module);
        }
    }
}
