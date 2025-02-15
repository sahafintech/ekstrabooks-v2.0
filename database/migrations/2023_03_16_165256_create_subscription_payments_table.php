<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned();
            $table->string('order_id');
            $table->string('payment_method', 20);
            $table->bigInteger('user_package_id')->unsigned();
            $table->decimal('amount', 10, 2);
            $table->tinyInteger('status')->default(0);
            $table->bigInteger('created_user_id');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('user_package_id')->references('id')->on('user_packages')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('subscription_payments');
    }
};
