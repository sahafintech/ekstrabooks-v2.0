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
            $table->foreignId('project_group_id')->constrained('project_groups')->nullable();
            $table->string('project_code');
            $table->string('project_name');
            $table->foreignId('customer_id')->constrained('customers')->nullable();
            $table->foreignId('project_manager_id')->constrained('employees')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', ['Planning', 'In Progress', 'Completed']);
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
