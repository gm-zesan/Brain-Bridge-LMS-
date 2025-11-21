<?php

namespace Database\Seeders;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::create([
            'name' => 'My Admin',
            'email' => 'myadmin@gmail.com',
            'password' => bcrypt('12345678'),
            'firebase_uid' => 'uLXZHkHd6zRb1U9kXifJZLJ590A3',
            'created_at' => '2025-07-22 05:44:01',
            'updated_at' => '2025-07-31 00:00:29',
        ]);
        $admin->assignRole('admin');

        $teacher = User::create([
            'name' => 'Mashter',
            'email' => 'mashter@gmail.com',
            'password' => bcrypt('112233'),
            'firebase_uid' => 'HMRrniRn8XOd020uuHmaSvSO2B63',
            'created_at' => '2025-10-21 19:52:55',
            'updated_at' => '2025-10-21 19:52:55',
        ]);
        $teacher->assignRole('teacher');
        Teacher::create([
            'user_id' => $teacher->id,
            'teacher_level_id' => 1,
            'title' => 'Senior Lecturer',
            'created_at' => '2025-10-21 19:52:55',
            'updated_at' => '2025-10-21 19:52:55',
        ]);



        $student = User::create([
            'name' => 'Rabby',
            'email' => 'rabby@gmail.com',
            'password' => bcrypt('112233'),
            'firebase_uid' => 'Z5oQXn9J07TZ693TxOwv88xXSzm1',
            'created_at' => '2025-10-21 19:52:55',
            'updated_at' => '2025-10-21 19:52:55',
        ]);
        $student->assignRole('student');
    }
}
