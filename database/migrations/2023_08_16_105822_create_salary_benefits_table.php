<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('salary_benefits', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('employee_benefit_id')->unsigned();
            $table->date('date')->nullable();
            $table->string('description', 191)->nullable();
            $table->decimal('amount', 28, 8);
            $table->string('type', 20)->default('add')->comment('add | deduct');
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('business_id')->unsigned();
            $table->bigInteger('created_user_id')->nullable();
            $table->bigInteger('updated_user_id')->nullable();
            $table->bigInteger('deleted_user_id')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('employee_benefit_id')->references('id')->on('employee_benefits')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('business_id')->references('id')->on('business')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('salary_benefits');
    }
};
