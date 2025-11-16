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
        Schema::create('lesson_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('slot_id')->constrained('available_slots')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');

            $table->date('scheduled_date');
            $table->dateTime('scheduled_start_time');
            $table->dateTime('scheduled_end_time');

            $table->enum('session_type', ['one_to_one', 'group'])->default('one_to_one');
            $table->enum('status', ['scheduled', 'completed', 'cancelled'])->default('scheduled');
            $table->decimal('price', 8, 2)->nullable();

            $table->string('meeting_platform')->nullable(); // zoom or google_meet
            $table->string('meeting_link')->nullable();     // full meeting url
            $table->string('meeting_id')->nullable();       // zoom/google meeting ID
            $table->text('description')->nullable();

            $table->string('payment_status')->default('pending'); // pending, paid, failed, refunded
            $table->string('payment_intent_id')->nullable();
            $table->string('payment_method')->nullable(); // stripe, paypal, etc.
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->string('currency', 3)->default('usd');
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_sessions');
    }
};
