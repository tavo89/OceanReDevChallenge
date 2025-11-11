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
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreign('currency')->references('code')->on('currencies');
        });

        Schema::table('receipts', function (Blueprint $table) {
            $table->foreign('currency')->references('code')->on('currencies');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['currency']);
        });

        Schema::table('receipts', function (Blueprint $table) {
            $table->dropForeign(['currency']);
        });
    }
};
