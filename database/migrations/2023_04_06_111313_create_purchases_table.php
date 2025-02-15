<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('vendor_id')->nullable()->unsigned();
            $table->string('title')->nullable();
            $table->string('bill_no', 100)->nullable();
            $table->string('po_so_number', 100)->nullable();
            $table->date('purchase_date');
            $table->date('due_date');
            $table->decimal('sub_total', 28, 8);
            $table->decimal('grand_total', 28, 8);
            $table->decimal('converted_total', 28, 8)->nullable();
            $table->string('currency');
            $table->decimal('exchange_rate', 28, 8);
            $table->decimal('paid', 28, 8)->default(0);
            $table->decimal('discount', 28, 8)->nullable();
            $table->tinyInteger('discount_type')->default(0)->comment('0 = Percentage | 1 = Fixed');
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->tinyInteger('status')->default(0);
            $table->tinyInteger('cash')->default(0);
            $table->tinyInteger('order')->default(0);
            $table->tinyInteger('template_type')->default(0)->comment('0 = Predefined | 1 = Dynamic');
            $table->string('template', 50)->nullable();
            $table->text('note')->nullable();
            $table->text('footer')->nullable();
            $table->string('short_code')->nullable();
            $table->tinyInteger('email_send')->default(0);
            $table->datetime('email_send_at')->nullable();
            $table->tinyInteger('withholding_tax')->default(0);
            $table->tinyInteger('approval_status')->default(0);
            $table->bigInteger('created_by')->nullable()->unsigned();
            $table->bigInteger('approved_by')->nullable()->unsigned();
            $table->string('benificiary')->nullable();
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('business_id')->unsigned();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('purchases');
    }
};
