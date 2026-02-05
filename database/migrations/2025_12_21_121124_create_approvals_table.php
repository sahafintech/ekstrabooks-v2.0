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
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('ref_id');
            $table->string('ref_name');
            $table->unsignedBigInteger('action_user')->nullable();
            $table->tinyInteger('status')->default(0); // 0: pending, 1: approved, 2: rejected
            $table->text('comment')->nullable();
            $table->timestamp('action_date')->nullable();
            $table->string('checker_type', 20)->default('approval');
            $table->timestamps();

            $table->foreign('action_user')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};
