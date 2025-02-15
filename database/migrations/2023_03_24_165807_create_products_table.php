<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 10)->comment('product || service');
            $table->bigInteger('product_unit_id')->nullable()->unsigned();
            $table->decimal('purchase_cost', 28, 8)->nullable();
            $table->decimal('selling_price', 28, 8)->nullable();
            $table->string('image')->nullable();
            $table->text('descriptions')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('code')->nullable();
            $table->integer('reorder_point')->nullable();
            $table->tinyInteger('stock_management')->default(0);
            $table->decimal('stock', 10, 2)->nullable();
            $table->decimal('intial_stock', 10, 2)->nullable();
            $table->bigInteger('category_id')->nullable()->unsigned();
            $table->bigInteger('brand_id')->nullable()->unsigned();
            $table->tinyInteger('allow_for_selling')->default(0);
            $table->tinyInteger('allow_for_purchasing')->default(0);
            $table->tinyInteger('status')->default(1);
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('business_id')->unsigned();
            $table->bigInteger('created_user_id')->nullable();
            $table->bigInteger('updated_user_id')->nullable();
            $table->timestamps();

            $table->foreign('income_account_id')->references('id')->on('accounts')->restrictOnDelete();
            $table->foreign('expense_account_id')->references('id')->on('accounts')->restrictOnDelete();
            $table->foreign('product_unit_id')->references('id')->on('product_units')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('brand_id')->references('id')->on('brand')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('products');
    }
};
