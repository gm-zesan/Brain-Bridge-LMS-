<?php

namespace Database\Seeders;

use App\Models\Skill;
use App\Models\Subject;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SkillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $skills = Subject::whereNotNull('parent_id')->get();
        
        foreach($skills as $data)
        {
            Skill::create(
                [
                    'name' => $data->name,
                    'subject_id' => $data->id,
                ]
            );
        }
    }
}
