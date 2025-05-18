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
        Schema::create('project_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects');
            $table->foreignId('project_task_id')->constrained('project_tasks');
            $table->foreignId('cost_code_id')->constrained('cost_codes');
            $table->foreignId('account_id')->constrained('accounts');
            $table->text('description');
            $table->string('uom'); // Unit of Measure
            $table->string('project_currency');
            $table->decimal('original_budgeted_quantity', 15, 2);
            $table->decimal('unit_rate', 15, 2);
            $table->decimal('original_budgeted_amount', 15, 2);
            $table->decimal('revised_budgeted_quantity', 15, 2);
            $table->decimal('revised_budgeted_amount', 15, 2);
            $table->decimal('revised_committed_quantity', 15, 2);
            $table->decimal('revised_committed_amount', 15, 2);
            $table->decimal('committed_open_quantity', 15, 2);
            $table->decimal('committed_open_amount', 15, 2);
            $table->decimal('committed_received_quantity', 15, 2);
            $table->decimal('committed_invoiced_quantity', 15, 2);
            $table->decimal('committed_invoiced_amount', 15, 2);
            $table->decimal('actual_quantity', 15, 2);
            $table->decimal('actual_amount', 15, 2);
            $table->string('type');
            $table->foreignId('business_id')->constrained('business');
            $table->foreignId('user_id')->constrained('users');
            $table->bigInteger('created_by')->nullable();
            $table->bigInteger('updated_by')->nullable();
            $table->bigInteger('deleted_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_budgets');
    }
};
