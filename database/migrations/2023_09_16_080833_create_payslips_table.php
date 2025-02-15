<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('employee_id')->unsigned();
            $table->bigInteger('transaction_id')->unsigned()->nullable();
            $table->integer('month');
            $table->integer('year');
            $table->decimal('current_salary', 28, 8);
            $table->text('taxes')->nullable();
            $table->decimal('tax_amount', 28, 8)->nullable();
            $table->decimal('absence_fine', 28, 8)->default(0);
            $table->decimal('advance', 28, 8)->default(0);
            $table->text("advance_description")->nullable();
            $table->decimal('total_allowance', 28, 8)->default(0);
            $table->decimal('total_deduction', 28, 8)->default(0);
            $table->decimal('net_salary', 8, 2);
            $table->tinyInteger('status')->deafult(0);

            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('business_id')->unsigned();
            $table->timestamps();

            $table->foreign('transaction_id')->references('id')->on('transactions')->nullOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('business_id')->references('id')->on('business')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('payslips');
    }
};
