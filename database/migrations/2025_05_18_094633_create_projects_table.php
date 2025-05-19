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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('project_group_id')->nullable();
            $table->string('project_code')->unique();
            $table->string('project_name');
            $table->bigInteger('customer_id')->nullable();
            $table->bigInteger('project_manager_id')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', ['Planning', 'In Progress', 'Completed', 'On Hold', 'Cancelled', 'Archived']);
            $table->enum('priority', ['Low', 'Medium', 'High', 'Critical']);
            $table->date('completion_date')->nullable();
            $table->string('project_currency')->nullable();
            $table->text('description')->nullable();
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
        Schema::dropIfExists('projects');
    }
};
