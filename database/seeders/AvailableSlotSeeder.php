<?php

namespace Database\Seeders;

use App\Models\AvailableSlot;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AvailableSlotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $available_slots = array(
            array('id' => '1','teacher_id' => '1','subject_id' => '1','title' => 'My title','type' => 'one_to_one','price' => '50.00','description' => 'string','from_date' => '2025-11-18','to_date' => '2025-11-20','start_time' => '07:00:00','end_time' => '09:00:00','is_booked' => '1','max_students' => '1','booked_count' => '1','created_at' => '2025-11-20 14:20:03','updated_at' => '2025-11-20 14:20:27'),
            array('id' => '2','teacher_id' => '1','subject_id' => '1','title' => 'Live Math Magic','type' => 'one_to_one','price' => '80.00','description' => 'Math Class All','from_date' => '2025-11-20','to_date' => '2025-11-25','start_time' => '16:00:00','end_time' => '18:00:00','is_booked' => '1','max_students' => '1','booked_count' => '1','created_at' => '2025-11-20 14:40:37','updated_at' => '2025-11-20 15:24:46'),
            array('id' => '3','teacher_id' => '1','subject_id' => '12','title' => 'React Js Bootcamp','type' => 'one_to_one','price' => '111.00','description' => 'React Js Bootcamp class live','from_date' => '2025-11-20','to_date' => '2025-11-30','start_time' => '18:00:00','end_time' => '21:00:00','is_booked' => '1','max_students' => '1','booked_count' => '1','created_at' => '2025-11-20 17:04:51','updated_at' => '2025-11-21 06:02:15'),
            array('id' => '4','teacher_id' => '1','subject_id' => '13','title' => 'DOM Manipulation Live','type' => 'one_to_one','price' => '105.00','description' => 'DOM Manipulation Live Class','from_date' => '2025-11-21','to_date' => '2025-11-30','start_time' => '10:00:00','end_time' => '12:00:00','is_booked' => '1','max_students' => '1','booked_count' => '1','created_at' => '2025-11-20 17:06:20','updated_at' => '2025-11-21 05:02:39'),
            array('id' => '5','teacher_id' => '2','subject_id' => '12','title' => 'React JS Master Class','type' => 'one_to_one','price' => '30.00','description' => 'React JS Master Class now available in this scedule.','from_date' => '2025-11-21','to_date' => '2025-11-30','start_time' => '21:00:00','end_time' => '23:00:00','is_booked' => '0','max_students' => '1','booked_count' => '0','created_at' => '2025-11-20 17:52:20','updated_at' => '2025-11-20 17:52:20'),
            array('id' => '6','teacher_id' => '2','subject_id' => '7','title' => 'Cover Drive Master class','type' => 'one_to_one','price' => '50.00','description' => 'Cover Drive Master class by Steven Smith','from_date' => '2025-11-21','to_date' => '2025-11-30','start_time' => '08:00:00','end_time' => '11:00:00','is_booked' => '0','max_students' => '1','booked_count' => '0','created_at' => '2025-11-21 04:04:30','updated_at' => '2025-11-21 04:04:30')
        );
        foreach ($available_slots as $slot) {
            AvailableSlot::create($slot);
        }
    }
}
