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
        Schema::create('medical_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->integer('patient_id')->nullable();
            $table->date('date')->nullable();
            $table->string('ocular_history')->nullable();
            $table->string('occupation')->nullable();
            $table->string('va_unaided_re')->nullable();
            $table->string('va_unaided_le')->nullable();
            $table->string('va_aided_re')->nullable();
            $table->string('va_aided_le')->nullable();
            $table->string('va_pinhole_re')->nullable();
            $table->string('va_pinhole_le')->nullable();
            $table->string('rf_unaided_d_re')->nullable();
            $table->string('rf_unaided_d_le')->nullable();
            $table->string('rf_unaided_n_re')->nullable();
            $table->string('rf_unaided_n_le')->nullable();
            $table->string('rf_aided_d_re')->nullable();
            $table->string('rf_aided_d_le')->nullable();
            $table->string('rf_aided_n_re')->nullable();
            $table->string('rf_aided_n_le')->nullable();
            $table->string('rf_best_corrected_le')->nullable();
            $table->string('rf_best_corrected_re')->nullable();
            $table->string('rf_test_type_used_re')->nullable();
            $table->string('rf_test_type_used_le')->nullable();
            $table->string('rf_lensometer_le')->nullable();
            $table->string('rf_lensometer_re')->nullable();
            $table->string('rf_autorefraction_re')->nullable();
            $table->string('rf_autorefraction_le')->nullable();
            $table->string('rf_dry_retinoscopy_re')->nullable();
            $table->string('rf_dry_retinoscopy_le')->nullable();
            $table->string('rf_wet_retinoscopy_re')->nullable();
            $table->string('rf_wet_retinoscopy_le')->nullable();
            $table->string('rf_subjective_re')->nullable();
            $table->string('rf_subjective_le')->nullable();
            $table->string('rf_near_re')->nullable();
            $table->string('rf_near_le')->nullable();
            $table->string('rf_final_prescription_re')->nullable();
            $table->string('rf_final_prescription_le')->nullable();
            $table->tinyInteger('eso')->default(0)->comment('0 = No | 1 = Yes')->nullable();
            $table->tinyInteger('exo')->default(0)->comment('0 = No | 1 = Yes')->nullable();
            $table->tinyInteger('hypo')->default(0)->comment('0 = No | 1 = Yes')->nullable();
            $table->tinyInteger('hyper')->default(0)->comment('0 = No | 1 = Yes')->nullable();
            $table->string('eso_distance_5m_6m')->nullable();
            $table->string('eso_near_30cm_50cm')->nullable();
            $table->string('exo_distance_5m_6m')->nullable();
            $table->string('exo_near_30cm_50cm')->nullable();
            $table->string('hypo_distance_5m_6m')->nullable();
            $table->string('hypo_near_30cm_50cm')->nullable();
            $table->string('hyper_distance_5m_6m')->nullable();
            $table->string('hyper_near_30cm_50cm')->nullable();
            $table->tinyInteger('tropia')->default(0)->comment('0 = No | 1 = Yes')->nullable();
            $table->tinyInteger('phoria')->default(0)->comment('0 = No | 1 = Yes')->nullable();
            $table->bigInteger('created_user_id')->nullable();
            $table->bigInteger('updated_user_id')->nullable();
            $table->bigInteger('deleted_user_id')->nullable();
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('business_id')->unsigned();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_records');
    }
};
