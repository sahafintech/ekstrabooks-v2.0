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
        Schema::create('salary_advances', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('employee_id')->unsigned();
            $table->double('amount');
            $table->string('currency');
            $table->double('exchange_rate');
            $table->date('date');
            $table->bigInteger('payment_account_id')->unsigned()->nullable()->default(null);
            $table->bigInteger('advance_account_id')->unsigned()->nullable()->default(null);
            $table->integer('payroll_month');
            $table->integer('payroll_year');
            $table->longText('notes')->nullable()->default(null);
            $table->tinyInteger('approval_status')->default(0);

            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('business_id')->unsigned();

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('payment_account_id')->references('id')->on('accounts')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('advance_account_id')->references('id')->on('accounts')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');

            $table->bigInteger('created_user_id')->nullable();
            $table->bigInteger('updated_user_id')->nullable();
            $table->bigInteger('deleted_user_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_advances');
    }
};
