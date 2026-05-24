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
        Schema::create('policy_certificate_section_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_certificate_section_id');
            $table->json('data');
            $table->integer('sort_order')->default(0);
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('business_id')->unsigned();
            $table->bigInteger('created_user_id')->nullable();
            $table->bigInteger('updated_user_id')->nullable();
            $table->bigInteger('deleted_user_id')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('policy_certificate_section_id', 'pcs_rows_section_fk')
                ->references('id')
                ->on('policy_certificate_sections')
                ->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policy_certificate_section_rows');
    }
};
