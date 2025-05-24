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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('customer_id')->unsigned();
            $table->string('title')->nullable();
            $table->string('invoice_number', 100)->nullable();
            $table->string('order_number', 100)->nullable();
            $table->date('invoice_date')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('sub_total', 28, 8);
            $table->decimal('grand_total', 28, 8);
            $table->decimal('converted_total', 28, 8)->nullable();
            $table->string('currency');
            $table->decimal('exchange_rate', 28, 8);
            $table->decimal('paid', 28, 8)->default(0);
            $table->decimal('discount', 28, 8)->nullable();
            $table->tinyInteger('discount_type')->default(0)->comment('0 = Percentage | 1 = Fixed');
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('template_type')->default(0)->comment('0 = Predefined | 1 = Dynamic');
            $table->string('template', 50)->nullable();
            $table->bigInteger('project_id')->nullable();
            $table->text('note')->nullable();
            $table->text('footer')->nullable();
            $table->integer('queue_number')->default(0);
            $table->string('short_code')->nullable();
            $table->bigInteger('parent_id')->nullable(); // Recurring Invoice ID
            $table->tinyInteger('email_send')->default(0);
            $table->datetime('email_send_at')->nullable();
            //Recurring Invoice
            $table->tinyInteger('is_recurring')->default(0);
            $table->integer('recurring_completed')->default(0);
            $table->date('recurring_start')->nullable();
            $table->date('recurring_end')->nullable();
            $table->integer('recurring_value')->nullable();
            $table->string('recurring_type', 20)->nullable();
            $table->date('recurring_invoice_date')->nullable();
            $table->string('recurring_due_date', 20)->nullable();
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('business_id')->unsigned();
            $table->bigInteger('client_id')->nullable();
            $table->timestamps();
            // deffered invoice
            $table->string('invoice_category')->nullable();
            $table->tinyInteger('is_deffered')->default(0);
            $table->date('deffered_start')->nullable();
            $table->date('deffered_end')->nullable();
            $table->integer('active_days')->nullable();
            $table->decimal('cost_per_day', 28, 8)->nullable();
            $table->bigInteger('created_by')->nullable()->unsigned();
            $table->bigInteger('updated_by')->nullable()->unsigned();
            $table->text('attachments')->nullable();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
