<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('business_id')->unsigned();
            $table->string('name', 50);
            $table->string('company_name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('registration_no')->nullable();
            $table->string('vat_id')->nullable();
            $table->string('mobile', 50)->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('contract_no')->nullable();
            $table->string('zip')->nullable();
            $table->text('address')->nullable();
            $table->string('profile_picture')->nullable();
            $table->text('custom_fields')->nullable();
            $table->bigInteger('created_user_id')->nullable();
            $table->bigInteger('updated_user_id')->nullable();
            $table->bigInteger('deleted_user_id')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('vendors');
    }
};
