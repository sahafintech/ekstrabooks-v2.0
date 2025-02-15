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
        Schema::create('hold_pos_invoice_item_taxes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('hold_pos_invoice_id')->unsigned();
            $table->bigInteger('hold_pos_invoice_item_id')->unsigned();
            $table->bigInteger('tax_id')->unsigned();
            $table->string('name', 100);
            $table->decimal('amount', 28, 8);
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('business_id')->unsigned();
            $table->timestamps();

            $table->foreign('hold_pos_invoice_id')->references('id')->on('hold_pos_invoices')->onDelete('cascade');
            $table->foreign('hold_pos_invoice_item_id')->references('id')->on('hold_pos_invoice_items')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hold_pos_invoice_item_taxes');
    }
};
