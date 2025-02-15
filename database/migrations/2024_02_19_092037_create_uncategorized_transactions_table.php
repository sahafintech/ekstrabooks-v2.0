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
        Schema::create('uncategorized_transactions', function (Blueprint $table) {
            $table->id();
            $table->datetime('date');
            $table->bigInteger('account_id')->unsigned()->nullable();
            $table->decimal('deposit', 28, 8);
            $table->decimal('withdrawal', 28, 8);
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('business_id')->unsigned();
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->restrictOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uncategorized_transactions');
    }
};
