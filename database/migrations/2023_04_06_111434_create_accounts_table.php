<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_code');
            $table->string('account_name', 255);
            $table->string('account_type', 50);
            $table->date('opening_date');
            $table->string('account_number', 50)->nullable();
            $table->string('currency', 5)->nullable();
            $table->decimal('opening_balance', 28, 8);
            $table->text('description')->nullable();
            $table->tinyInteger('locked')->default(0);
            $table->string('dr_cr');
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('business_id')->unsigned();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->nullable()->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('business')->nullable()->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('accounts');
    }
};
