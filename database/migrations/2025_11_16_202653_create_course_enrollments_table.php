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
        Schema::create('course_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('enrolled_at');
            
            // Payment fields
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->string('currency', 3)->default('usd');
            $table->enum('payment_status', ['free', 'paid', 'refunded'])->default('free');
            $table->string('payment_intent_id')->nullable();
            $table->string('payment_method')->nullable();
            $table->timestamp('paid_at')->nullable();
            
            // Enrollment status
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->decimal('progress_percentage', 5, 2)->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_enrollments');
    }
};
