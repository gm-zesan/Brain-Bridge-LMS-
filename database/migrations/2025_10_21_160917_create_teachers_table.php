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
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('teacher_level_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('introduction_video')->nullable();
            $table->decimal('base_pay', 8, 2)->default(0);
            $table->integer('total_sessions')->default(0);
            $table->integer('average_rating')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
