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
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('package_type', 30);
            $table->decimal('cost', 10, 2);
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('is_popular')->default(0);
            $table->decimal('discount', 10, 2)->nullable();
            $table->integer('trial_days')->default(0);
            //Features List
            $table->string('user_limit')->nullable();
            $table->string('invoice_limit')->nullable();
            $table->string('quotation_limit')->nullable();
            $table->tinyInteger('recurring_invoice')->default(0)->comment('1 = Yes | 0 = No');
            $table->tinyInteger('deffered_invoice')->default(0)->comment('1 = Yes | 0 = No');
            $table->string('customer_limit')->nullable();
            $table->string('business_limit')->nullable();
            $table->tinyInteger('invoice_builder')->default(0)->comment('1 = Yes | 0 = No');
            $table->tinyInteger('online_invoice_payment')->default(0)->comment('1 = Yes | 0 = No');
            $table->tinyInteger('pos')->default(0)->comment('1 = Yes | 0 = No');
            $table->integer('storage_limit')->default(0);
            $table->tinyInteger('medical_record')->default(0)->comment('1 = Yes | 0 = No');
            $table->tinyInteger('prescription')->default(0)->comment('1 = Yes | 0 = No');
            $table->text('others')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
