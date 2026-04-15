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
        Schema::create('inventory_transfer_comments', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('inventory_transfer_id')->unsigned();
            $table->text('comment');
            $table->string('type')->default('rejection'); // rejection, general
            $table->bigInteger('created_user_id')->nullable();
            $table->timestamps();

            $table->foreign('inventory_transfer_id')->references('id')->on('inventory_transfers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transfer_comments');
    }
};
