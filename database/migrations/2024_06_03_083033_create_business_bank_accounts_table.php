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
        Schema::create('business_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('business_id')->unsigned();
            $table->string('bank_name');
            $table->string('account_name');
            $table->string('account_number');
            $table->string('account_currency')->nullable();
            $table->string('branch')->nullable();
            $table->string('swift_code')->nullable();
            $table->string('display_on_invoice')->default(0);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_bank_accounts');
    }
};
