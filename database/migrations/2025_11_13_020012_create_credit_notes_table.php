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
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->string('credit_note_number', 191)->unique();
            $table->unsignedBigInteger('invoice_id');
            $table->date('issue_date');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3);
            $table->unsignedBigInteger('period_id');
            $table->decimal('exchange_rate', 15, 6);
            $table->decimal('base_currency_amount', 15, 2);
            $table->text('reason')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('invoice_id')
                ->references('id')
                ->on('invoices')
                ->onDelete('cascade');

            $table->foreign('period_id')
                ->references('id')
                ->on('accounting_periods')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_notes');
    }
};
