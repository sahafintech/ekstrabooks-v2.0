<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('quotation_id')->unsigned();
            $table->bigInteger('insurance_category_id')->unsigned()->nullable();
            $table->bigInteger('product_id')->unsigned();
            $table->string('product_name')->nullable();
            $table->text('description')->nullable();

            $table->bigInteger('rating_rule_id')->unsigned()->nullable();

            $table->string('calculation_type', 100)->default('manual_premium');
            // percentage_of_amount | fixed_per_quantity | fixed_amount | manual_premium | tiered_rate | contribution_table

            $table->string('rate_type', 50)->default('fixed');
            // percentage | fixed | manual | range

            $table->decimal('rate_value', 28, 8)->default(0);
            $table->decimal('basis_amount', 28, 8)->default(0);
            $table->decimal('basis_quantity', 28, 8)->default(1);

            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_cost', 28, 8)->default(0);
            $table->decimal('sub_total', 28, 8)->default(0);

            $table->json('metadata_json')->nullable();

            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('business_id')->unsigned();
            $table->bigInteger('created_user_id')->nullable();
            $table->bigInteger('updated_user_id')->nullable();
            $table->bigInteger('deleted_user_id')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('quotation_id')->references('id')->on('quotations')->onDelete('cascade');
            $table->foreign('insurance_category_id')->references('id')->on('insurance_categories')->onDelete('set null');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
    }
};
