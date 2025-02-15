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
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('customer_id')->unsigned();
            $table->string('title')->nullable();
            $table->string('quotation_number', 100);
            $table->string('po_so_number', 100)->nullable();
            $table->date('quotation_date');
            $table->date('expired_date');
            $table->decimal('sub_total', 28, 8);
            $table->decimal('grand_total', 28, 8);
            $table->decimal('converted_total', 28, 8)->nullable();
            $table->string('currency');
            $table->decimal('exchange_rate', 28, 8);
            $table->decimal('discount', 28, 8)->nullable();
            $table->tinyInteger('discount_type')->default(0)->comment('0 = Percentage | 1 = Fixed');
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->tinyInteger('template_type')->default(0)->comment('0 = Predefined | 1 = Dynamic');
            $table->string('template', 50)->nullable();
            $table->text('note')->nullable();
            $table->text('footer')->nullable();
            $table->string('short_code')->nullable();
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('business_id')->unsigned();
            $table->timestamps();

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
        Schema::dropIfExists('quotations');
    }
};
