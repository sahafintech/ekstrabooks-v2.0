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
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['income_category_id']);
            $table->dropForeign(['expense_category_id']);
            $table->foreign('income_category_id')->references('id')->on('transaction_categories')->cascadeOnDelete();
            $table->foreign('expense_category_id')->references('id')->on('transaction_categories')->cascadeOnDelete();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropForeign(['transaction_category_id']);
            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('transaction_category_id')->references('id')->on('transaction_categories')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothiing
    }
};
