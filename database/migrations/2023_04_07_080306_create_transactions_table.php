<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->datetime('trans_date');
            $table->bigInteger('account_id')->unsigned()->nullable();
            $table->bigInteger('transaction_category_id')->unsigned()->nullable();
            $table->string('dr_cr', 2);
            $table->string('type', 20)->comment('income|expense|transfer|others');
            $table->decimal('transaction_amount', 28, 8);
            $table->decimal('currency_rate', 28, 8);
            $table->string('transaction_currency');
            $table->decimal('base_currency_amount', 28, 8);
            $table->decimal('ref_amount', 28, 8)->nullable();
            $table->string('transaction_method', 100)->nullable();
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->string('attachment')->nullable();
            $table->varchar('ref_id', 255)->nullable();
            $table->string('ref_type')->nullable();
            $table->bigInteger('customer_id')->nullable(); // Income By Customer
            $table->bigInteger('vendor_id')->nullable(); // Expense By Vendor
            $table->bigInteger('employee_id')->nullable(); // Expense By Employee
            $table->bigInteger('tax_id')->nullable(); // Expense By Tax
            $table->bigInteger('project_id')->nullable();
            $table->bigInteger('project_task_id')->nullable();
            $table->bigInteger('cost_code_id')->nullable();
            $table->decimal('quantity', 28, 8)->nullable();
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('business_id')->unsigned();
            $table->bigInteger('created_user_id')->nullable();
            $table->bigInteger('updated_user_id')->nullable();
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->restrictOnDelete();
            $table->foreign('transaction_category_id')->references('id')->on('transaction_categories')->restrictOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('transactions');
    }
};
