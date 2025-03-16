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
        Schema::create('tax_calculation_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('method_type'); // 'progressive' or 'fixed'
            $table->text('description')->nullable();
            $table->unsignedBigInteger('business_id')->nullable();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->timestamps();
        });
        
        // Create a separate table for tax brackets (used by progressive taxation)
        Schema::create('tax_brackets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tax_calculation_method_id');
            $table->decimal('income_from', 15, 2);
            $table->decimal('income_to', 15, 2)->nullable();
            $table->decimal('rate', 8, 2); // Percentage rate
            $table->decimal('fixed_amount', 15, 2)->default(0); // Fixed amount for this bracket
            $table->foreign('tax_calculation_method_id')->references('id')->on('tax_calculation_methods')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_brackets');
        Schema::dropIfExists('tax_calculation_methods');
    }
};
