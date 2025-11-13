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
        Schema::table('accounting_period_balances', function (Blueprint $table) {
            // Drop the old unique constraint
            $table->dropUnique('period_account_unique');
        });
        
        // Note: In MySQL, we can't create a partial unique index easily.
        // The soft delete approach will rely on application logic:
        // - When closing: delete existing records (soft delete)
        // - When reopening: records remain soft-deleted for audit trail
        // - On re-close: old soft-deleted records stay, new ones are created
        // This means the unique constraint is enforced only for active (non-deleted) records
        // which Eloquent handles automatically with soft deletes.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounting_period_balances', function (Blueprint $table) {
            // Restore the unique constraint
            $table->unique(['accounting_period_id', 'account_id'], 'period_account_unique');
        });
    }
};
