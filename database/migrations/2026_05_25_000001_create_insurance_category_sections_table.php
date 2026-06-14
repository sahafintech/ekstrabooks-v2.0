<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_category_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insurance_category_id')->constrained('insurance_categories')->cascadeOnDelete();
            $table->string('purpose', 50)->default('certificate'); // certificate | quotation
            $table->string('title');
            $table->string('type')->default('fields');
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('fields_json')->nullable();
            $table->json('columns_json')->nullable();
            $table->json('rows_json')->nullable();
            $table->text('content')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_category_sections');
    }
};
