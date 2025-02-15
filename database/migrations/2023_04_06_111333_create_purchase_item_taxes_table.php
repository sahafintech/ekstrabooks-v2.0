<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('purchase_item_taxes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('purchase_id')->unsigned();
            $table->bigInteger('purchase_item_id')->unsigned();
            $table->bigInteger('tax_id')->unsigned();
            $table->string('name', 100);
            $table->decimal('amount', 28, 8);
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('business_id')->unsigned();
            $table->timestamps();

            $table->foreign('purchase_id')->references('id')->on('purchases')->onDelete('cascade');
            $table->foreign('purchase_item_id')->references('id')->on('purchase_items')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('purchase_item_taxes');
    }
};
