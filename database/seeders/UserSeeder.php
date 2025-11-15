<?php

namespace Database\Seeders;

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
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('12345678'),
            'created_at' => '2025-07-22 05:44:01',
            'updated_at' => '2025-07-31 00:00:29',
        ]);
        $admin->assignRole('admin');


        $users = array(
            array('name' => 'GM Zesan','email' => 'zesan7767@gmail.com','phone' => NULL,'firebase_uid' => 'QlZM9hjqnYRGYTWXl5ZNgwohc343','email_verified_at' => NULL,'password' => '$2y$12$aLJIzgDjuqbs55PadZU2eu6hYnuOkRhAJ6h2qBq4mu8CU0IDX5bMG','bio' => NULL,'address' => NULL,'profile_picture' => NULL,'is_active' => '0','remember_token' => NULL,'created_at' => '2025-10-21 19:52:55','updated_at' => '2025-10-21 19:52:55'),
            array('name' => 'Rabby','email' => 'rabby@gmail.com','phone' => NULL,'firebase_uid' => 'dJyGJG6XHvebPb3agEC96SnnbKk1','email_verified_at' => NULL,'password' => '$2y$12$PFKNFBwH12NJGOPoz1Ly1OVHbb1rPtiUZtf5eEyyiyfj3WqcuapIS','bio' => NULL,'address' => NULL,'profile_picture' => NULL,'is_active' => '0','remember_token' => NULL,'created_at' => '2025-10-23 16:44:51','updated_at' => '2025-10-23 16:44:51'),
            array('name' => 'Mashter','email' => 'mashter@gmail.com','phone' => NULL,'firebase_uid' => '0u02XV9kUoevPRN6xCEwTxabP2F2','email_verified_at' => NULL,'password' => '$2y$12$sgEyecDH2/M6CFmwAtaz4.z4UBkheX2qTWrwF/XsiWzq44IhUxYOK','bio' => NULL,'address' => NULL,'profile_picture' => NULL,'is_active' => '0','remember_token' => NULL,'created_at' => '2025-10-23 16:44:51','updated_at' => '2025-10-23 16:44:51'),
        );

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
