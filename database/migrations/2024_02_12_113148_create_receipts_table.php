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
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('customer_id')->nullable()->unsigned();
            $table->string('title')->nullable();
            $table->string('receipt_number', 100)->nullable();
            $table->string('order_number', 100)->nullable();
            $table->date('receipt_date');
            $table->decimal('sub_total', 28, 8);
            $table->decimal('grand_total', 28, 8);
            $table->decimal('converted_total', 28, 8)->nullable();
            $table->string('currency');
            $table->decimal('exchange_rate', 28, 8);
            $table->decimal('discount', 28, 8)->nullable();
            $table->tinyInteger('discount_type')->default(0)->comment('0 = Percentage | 1 = Fixed');
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->tinyInteger('template_type')->default(0)->comment('0 = Predefined | 1 = Dynamic');
            $table->string('template')->default('default');
            $table->text('note')->nullable();
            $table->text('footer')->nullable();
            $table->integer('queue_number')->default(0);
            $table->string('short_code')->nullable();
            $table->tinyInteger('email_send')->default(0);
            $table->datetime('email_send_at')->nullable();
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('business_id')->unsigned();

            $table->foreign('customer_id')->references('id')->on('customers')->nullable()->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
