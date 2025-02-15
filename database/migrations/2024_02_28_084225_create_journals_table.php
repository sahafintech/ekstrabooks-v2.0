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
        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('journal_number');
            $table->decimal('transaction_amount', 28, 8);
            $table->decimal('currency_rate', 28, 8);
            $table->string('transaction_currency');
            $table->decimal('base_currency_amount', 28, 8);
            $table->tinyInteger('status')->default(0);
            $table->bigInteger('created_by')->unsigned()->nullable();
            $table->bigInteger('approved_by')->unsigned()->nullable();
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('business_id')->unsigned();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('business')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journals');
    }
};
