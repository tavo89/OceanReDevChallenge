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
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique();
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->unsignedBigInteger('period_id');
            $table->decimal('exchange_rate', 10, 6)->nullable();
            $table->decimal('base_currency_amount', 15, 2)->nullable(); // Monto convertido a moneda base
            $table->timestamps();

            $table->foreign('period_id')->references('id')->on('accounting_periods')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropForeign(['period_id']);
        });
        
        Schema::dropIfExists('receipts');
    }
};
