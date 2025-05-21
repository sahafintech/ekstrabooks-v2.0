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
        Schema::create('project_subcontract_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_subcontract_id')->constrained('project_subcontracts');
            $table->foreignId('project_task_id')->constrained('project_tasks');
            $table->foreignId('cost_code_id')->constrained('cost_codes');
            $table->string('uom')->nullable(); // Unit of Measure
            $table->decimal('quantity', 15, 2)->nullable();
            $table->decimal('unit_cost', 15, 2)->nullable();
            $table->decimal('sub_total', 15, 2)->nullable();
            $table->bigInteger('account_id')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('business_id')->constrained('business');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_subcontract_tasks');
    }
};
