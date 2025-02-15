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
        Schema::create('deffered_receive_payments', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('payment_id')->unsigned();
            $table->bigInteger('deffered_payment_id')->unsigned();
            $table->decimal('amount', 8, 2);
            $table->timestamps();

            $table->foreign('payment_id')->references('id')->on('receive_payments')->onDelete('cascade');
            $table->foreign('deffered_payment_id')->references('id')->on('deffered_payments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deffered_receive_payments');
    }
};
