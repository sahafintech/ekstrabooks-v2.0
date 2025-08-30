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
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->date('date')->nullable();
            $table->date('result_date')->nullable();
            $table->string('dist_sph_re')->nullable();
            $table->string('dist_cyl_re')->nullable();
            $table->string('dist_axis_re')->nullable();
            $table->string('dist_va_re')->nullable();
            $table->string('dist_sph_le')->nullable();
            $table->string('dist_cyl_le')->nullable();
            $table->string('dist_axis_le')->nullable();
            $table->string('dist_va_le')->nullable();
            $table->string('near_sph_re')->nullable();
            $table->string('near_cyl_re')->nullable();
            $table->string('near_axis_re')->nullable();
            $table->string('near_va_re')->nullable();
            $table->string('near_sph_le')->nullable();
            $table->string('near_cyl_le')->nullable();
            $table->string('near_axis_le')->nullable();
            $table->string('near_va_le')->nullable();
            $table->string('ipd')->nullable();
            $table->string('glasses')->nullable();
            $table->string('plastic')->nullable();
            $table->string('polycarbonate')->nullable();
            $table->string('contact_lenses')->nullable();
            $table->string('photochromatic_lenses')->nullable();
            $table->string('bi_focal_lenses')->nullable();
            $table->string('progressive_lenses')->nullable();
            $table->string('anti_reflection_coating')->nullable();
            $table->string('high_index_lenses')->nullable();
            $table->string('single_vision')->nullable();
            $table->string('white_lenses')->nullable();
            $table->string('blue_cut')->nullable();
            $table->text('description')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->bigInteger('created_user_id')->nullable();
            $table->bigInteger('updated_user_id')->nullable();
            $table->bigInteger('deleted_user_id')->nullable();
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('business_id')->unsigned();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
