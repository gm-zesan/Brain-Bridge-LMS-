<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subjects = array(
            array('id' => '1','name' => 'Mathematics','parent_id' => NULL,'created_at' => '2025-11-04 13:50:13','updated_at' => '2025-11-04 13:50:13'),
            array('id' => '3','name' => 'English','parent_id' => NULL,'created_at' => '2025-11-04 14:13:52','updated_at' => '2025-11-05 15:17:25'),
            array('id' => '7','name' => 'Cricket','parent_id' => NULL,'created_at' => '2025-11-04 14:52:42','updated_at' => '2025-11-04 14:52:42'),
            array('id' => '8','name' => 'Football','parent_id' => NULL,'created_at' => '2025-11-04 14:52:48','updated_at' => '2025-11-04 14:52:48'),
            array('id' => '9','name' => 'Singing','parent_id' => NULL,'created_at' => '2025-11-04 14:53:10','updated_at' => '2025-11-04 14:53:10'),
            array('id' => '12','name' => 'React Js','parent_id' => NULL,'created_at' => '2025-11-04 15:04:40','updated_at' => '2025-11-04 15:04:40'),
            array('id' => '13','name' => 'JavaScript','parent_id' => NULL,'created_at' => '2025-11-04 15:04:46','updated_at' => '2025-11-04 15:04:46'),
        );

        foreach($subjects as $data)
        {
            Subject::create($data);
        }
    }
}
