<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('available_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->longText('recurrence_rule')->nullable();
            $table->boolean('is_booked')->default(false);
            $table->timestamps();

            $table->unique(['teacher_id', 'start_time', 'end_time'], 'unique_teacher_slot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('available_slots');
    }
};
