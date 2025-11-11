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
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('journal_entry_id');
            $table->unsignedBigInteger('account_id');
            $table->decimal('debit', 15, 2)->default(0)->comment('Debit amount, must be 0 if credit > 0');
            $table->decimal('credit', 15, 2)->default(0)->comment('Credit amount, must be 0 if debit > 0');
            $table->timestamps();

            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->onDelete('cascade');
            
            // Note: Validation that debit and credit are mutually exclusive will be handled in the model
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_entry_lines', function (Blueprint $table) {
            $table->dropForeign(['journal_entry_id']);
        });
        
        Schema::dropIfExists('journal_entry_lines');
    }
};
