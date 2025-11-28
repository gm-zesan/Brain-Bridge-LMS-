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
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('payment_method')->nullable(); // 'bank', 'paypal', 'stripe', etc.
            $table->string('bank_account_number')->nullable();
            $table->string('bank_routing_number')->nullable(); // For ACH in the USA
            $table->string('bank_name')->nullable();
            $table->string('paypal_email')->nullable();
            $table->string('stripe_account_id')->nullable();
            $table->string('tax_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            //
        });
    }
};
