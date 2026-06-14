<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_sections', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('quotation_id')->unsigned();
            $table->bigInteger('insurance_category_id')->unsigned();
            $table->bigInteger('insurance_category_section_id')->unsigned()->nullable();

            $table->string('title');
            $table->string('type', 100)->default('fields');
            $table->unsignedInteger('sort_order')->default(0);

            $table->json('data_json')->nullable();
            $table->text('content')->nullable();

            $table->timestamps();

            $table->foreign('quotation_id')->references('id')->on('quotations')->onDelete('cascade');
            $table->foreign('insurance_category_id')->references('id')->on('insurance_categories')->onDelete('cascade');
            $table->foreign('insurance_category_section_id')->references('id')->on('insurance_category_sections')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_sections');
    }
};
