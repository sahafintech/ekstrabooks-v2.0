<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rating_rules', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('insurance_category_id')->unsigned();
            $table->bigInteger('product_id')->unsigned()->nullable();

            $table->string('name');

            $table->string('calculation_type', 100);
            // percentage_of_amount | fixed_per_quantity | fixed_amount | manual_premium | tiered_rate | contribution_table

            $table->string('rate_type', 50);
            // percentage | fixed | manual | range

            $table->decimal('min_rate', 28, 8)->nullable();
            $table->decimal('max_rate', 28, 8)->nullable();
            $table->decimal('default_rate', 28, 8)->nullable();
            $table->decimal('minimum_premium', 28, 8)->nullable();
            $table->decimal('tax_rate', 10, 4)->nullable();

            $table->string('currency', 55)->nullable();

            $table->date('active_from')->nullable();
            $table->date('active_to')->nullable();
            $table->tinyInteger('is_active')->default(1);

            $table->json('metadata_json')->nullable();

            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('business_id')->unsigned();
            $table->timestamps();

            $table->foreign('insurance_category_id')->references('id')->on('insurance_categories')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rating_rules');
    }
};
