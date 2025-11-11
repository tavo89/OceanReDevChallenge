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
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('from_currency', 3); // USD
            $table->string('to_currency', 3); // EUR
            $table->decimal('rate', 10, 6); // 0.85 (1 USD = 0.85 EUR)
            $table->date('effective_date');
            $table->timestamps();

            $table->unique(['from_currency', 'to_currency', 'effective_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
