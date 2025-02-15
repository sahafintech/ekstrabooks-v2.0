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
        Schema::create('business_users', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('business_id')->unsigned();
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('role_id')->unsigned()->nullable();
            $table->bigInteger('owner_id')->unsigned();
            $table->tinyInteger('is_active')->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete(null);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_users');
    }
};
