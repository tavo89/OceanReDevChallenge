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
        Schema::create('accounting_period_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('accounting_period_id');
            $table->unsignedBigInteger('account_id');
            $table->string('account_code', 20);
            $table->string('account_name', 191);
            $table->string('account_type', 20);
            $table->decimal('total_debit', 15, 2)->default(0);
            $table->decimal('total_credit', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('accounting_period_id')
                ->references('id')
                ->on('accounting_periods')
                ->onDelete('cascade');
            
            $table->foreign('account_id')
                ->references('id')
                ->on('accounts')
                ->onDelete('cascade');

            // Unique constraint: one balance record per account per period
            $table->unique(['accounting_period_id', 'account_id'], 'period_account_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounting_period_balances', function (Blueprint $table) {
            $table->dropForeign(['accounting_period_id']);
            $table->dropForeign(['account_id']);
        });
        
        Schema::dropIfExists('accounting_period_balances');
    }
};
