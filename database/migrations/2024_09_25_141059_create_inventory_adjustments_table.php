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
        Schema::create('inventory_adjustments', function (Blueprint $table) {
            $table->id();
            $table->date('adjustment_date');
            $table->bigInteger('product_id')->unsigned();
            $table->bigInteger('account_id')->unsigned();
            $table->text('description')->nullable();
            $table->float('quantity_on_hand');
            $table->float('adjusted_quantity');
            $table->float('new_quantity_on_hand');
            $table->string('adjustment_type');
            $table->bigInteger('created_user_id')->nullable();
            $table->bigInteger('updated_user_id')->nullable();
            $table->bigInteger('deleted_user_id')->nullable();
            $table->bigInteger('business_id')->unsigned();
            $table->bigInteger('user_id')->unsigned();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustments');
    }
};
