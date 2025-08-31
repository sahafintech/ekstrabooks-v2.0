<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id', 100);
            $table->string('name', 50);
            $table->date('date_of_birth')->nullable();
            $table->string('email', 191)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('city', 191)->nullable();
            $table->string('country', 191)->nullable();
            $table->integer('working_hours')->nullable();
            $table->tinyInteger('time_sheet_based')->default(0);
            $table->integer('max_overtime_hours')->default(0);
            $table->decimal('basic_salary', 28, 8);

            $table->bigInteger('department_id')->unsigned()->nullable();
            $table->bigInteger('designation_id')->unsigned()->nullable();
            $table->date('joining_date');
            $table->date('end_date')->nullable();

            $table->string('bank_name', 191)->nullable();
            $table->string('branch_name', 191)->nullable();
            $table->string('account_name',191)->nullable();
            $table->string('account_number', 30)->nullable();

            $table->text('custom_fields')->nullable();
            $table->bigInteger('created_user_id')->nullable();
            $table->bigInteger('updated_user_id')->nullable();
            $table->bigInteger('deleted_user_id')->nullable();
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('business_id')->unsigned();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('department_id')->references('id')->on('departments')->restrictOnDelete();
            $table->foreign('designation_id')->references('id')->on('designations')->restrictOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('business_id')->references('id')->on('business')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('employees');
    }
};
