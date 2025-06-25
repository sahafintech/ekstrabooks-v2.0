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
        Schema::create('project_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('task_code');
            $table->string('description');
            $table->foreignId('project_id')->constrained('projects');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status');
            $table->float('completed_percent')->default(0);
            $table->foreignId('business_id')->constrained('business');
            $table->foreignId('user_id')->constrained('users');
            $table->bigInteger('created_by')->nullable();
            $table->bigInteger('updated_by')->nullable();
            $table->bigInteger('deleted_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['project_id', 'task_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_tasks');
    }
};
