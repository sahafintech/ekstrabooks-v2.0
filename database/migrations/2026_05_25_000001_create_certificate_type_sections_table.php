<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_type_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('certificate_type_id')->constrained('certificate_types')->cascadeOnDelete();
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
        Schema::dropIfExists('certificate_type_sections');
    }
};
