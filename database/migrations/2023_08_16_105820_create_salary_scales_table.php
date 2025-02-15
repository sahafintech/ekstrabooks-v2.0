<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('salary_scales', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('department_id')->unsigned();
            $table->bigInteger('designation_id')->unsigned();
            $table->string('grade');
            $table->decimal('basic_salary', 28, 8);
            $table->decimal('full_day_absence_fine', 28, 8)->default(0);
            $table->decimal('half_day_absence_fine', 28, 8)->default(0);
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('business_id')->unsigned();
            $table->timestamps();

            $table->foreign('department_id')->references('id')->on('departments')->cascadeOnDelete();
            $table->foreign('designation_id')->references('id')->on('designations')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('business_id')->references('id')->on('business')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('salary_scales');
    }
};
