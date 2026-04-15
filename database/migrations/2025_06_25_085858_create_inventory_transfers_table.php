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
        Schema::create('inventory_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number')->unique();
            $table->date('transfer_date');
            $table->bigInteger('from_entity_id')->unsigned();
            $table->bigInteger('to_entity_id')->unsigned();
            $table->enum('status', ['draft', 'sent', 'received', 'rejected', 'cancelled'])->default('draft');
            $table->text('remarks')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->bigInteger('created_user_id')->nullable();
            $table->bigInteger('updated_user_id')->nullable();
            $table->bigInteger('sent_user_id')->nullable();
            $table->bigInteger('received_user_id')->nullable();
            $table->bigInteger('rejected_user_id')->nullable();
            $table->bigInteger('cancelled_user_id')->nullable();
            $table->bigInteger('deleted_user_id')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('from_entity_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('to_entity_id')->references('id')->on('business')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transfers');
    }
};
