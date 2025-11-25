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
        Schema::create('teacher_levels', function (Blueprint $table) {
            $table->id();
            $table->string('level_name')->unique(); // e.g., Bronze, Silver, Gold, Platinum, Master
            $table->float('min_rating')->default(0); // Minimum average rating required for this level
            $table->longText('benefits')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_levels');
    }
};
