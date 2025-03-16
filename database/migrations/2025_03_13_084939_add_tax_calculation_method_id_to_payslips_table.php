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
        Schema::table('payslips', function (Blueprint $table) {
            $table->unsignedBigInteger('tax_calculation_method_id')->nullable()->after('taxes');
            $table->foreign('tax_calculation_method_id')->references('id')->on('tax_calculation_methods')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropForeign(['tax_calculation_method_id']);
            $table->dropColumn('tax_calculation_method_id');
        });
    }
};
