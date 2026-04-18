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
        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('quotation_id')->unsigned();
            $table->bigInteger('product_id')->unsigned();
            $table->string('product_name')->nullable();
            $table->text('description')->nullable();
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_cost', 28, 8);
            $table->decimal('sub_total', 28, 8);
            $table->string('family_size')->nullable();
            $table->decimal('sum_insured', 28, 8)->default(0);
            $table->decimal('inpatient_limit_per_family', 28, 8)->nullable();
            $table->decimal('inpatient_contribution_per_family', 28, 8)->nullable();
            $table->decimal('inpatient_total_contribution', 28, 8)->nullable();
            $table->decimal('maternity_limit_per_family', 28, 8)->nullable();
            $table->decimal('maternity_contribution_per_family', 28, 8)->nullable();
            $table->decimal('maternity_total_contribution', 28, 8)->nullable();
            $table->decimal('outpatient_limit_per_family', 28, 8)->nullable();
            $table->decimal('outpatient_contribution_per_family', 28, 8)->nullable();
            $table->decimal('outpatient_total_contribution', 28, 8)->nullable();
            $table->decimal('dental_limit_per_family', 28, 8)->nullable();
            $table->decimal('dental_contribution_per_family', 28, 8)->nullable();
            $table->decimal('dental_total_contribution', 28, 8)->nullable();
            $table->decimal('optical_limit_per_family', 28, 8)->nullable();
            $table->decimal('optical_contribution_per_family', 28, 8)->nullable();
            $table->decimal('optical_total_contribution', 28, 8)->nullable();
            $table->decimal('telemedicine_limit_per_family', 28, 8)->nullable();
            $table->decimal('telemedicine_contribution_per_family', 28, 8)->nullable();
            $table->decimal('telemedicine_total_contribution', 28, 8)->nullable();
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('business_id')->unsigned();
            $table->bigInteger('created_user_id')->nullable();
            $table->bigInteger('updated_user_id')->nullable();
            $table->bigInteger('deleted_user_id')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('quotation_id')->references('id')->on('quotations')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
    }
};
