<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $datas = array(
            array('id' => '1','name' => 'admin', 'guard_name' => 'web'),
            array('id' => '2','name' => 'teacher', 'guard_name' => 'web'),
            array('id' => '3','name' => 'student', 'guard_name' => 'web'),

        );
        foreach($datas as $data)
        {
            Role::create($data);
        }
    }
}
