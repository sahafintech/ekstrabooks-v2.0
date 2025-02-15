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
        Schema::create('receive_payments', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->bigInteger('customer_id')->unsigned();
            $table->bigInteger('account_id')->unsigned();
            $table->string('payment_method')->nullable();
            $table->string('reference')->nullable();
            $table->decimal('amount', 8, 2);
            $table->string('type');
            $table->tinyInteger('deffered_payment')->default(0);
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('business_id')->unsigned();
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receive_payments');
    }
};
