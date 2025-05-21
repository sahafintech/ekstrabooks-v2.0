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
        Schema::create('project_subcontract_taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_subcontract_id')->constrained('project_subcontracts');
            $table->foreignId('tax_id')->constrained('taxes');
            $table->string('name', 100);
            $table->decimal('amount', 28, 8);
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('business_id')->constrained('business');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_subcontract_taxes');
    }
};
