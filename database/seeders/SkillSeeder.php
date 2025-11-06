<?php

namespace Database\Seeders;

use App\Models\Skill;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SkillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $skills = array(
            array('id' => '1','name' => 'Bug Bountry','subject_id' => '12','created_at' => '2025-11-04 13:52:20','updated_at' => '2025-11-05 15:25:02'),
            array('id' => '2','name' => 'Batting','subject_id' => '7','created_at' => '2025-11-04 16:14:03','updated_at' => '2025-11-04 16:14:03'),
            array('id' => '4','name' => 'Development','subject_id' => '12','created_at' => '2025-11-04 16:16:44','updated_at' => '2025-11-04 16:16:44'),
            array('id' => '6','name' => 'Problem Solving','subject_id' => '1','created_at' => '2025-11-05 15:25:15','updated_at' => '2025-11-05 15:25:15')
        );
        foreach($skills as $data)
        {
            Skill::create($data);
        }
    }
}
