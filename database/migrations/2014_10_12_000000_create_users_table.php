<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('user_type', 20)->comment('admin | user');
            $table->tinyInteger('status')->default(1);
            $table->string('profile_picture')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip', 30)->nullable();
            $table->text('address')->nullable();
            $table->timestamp('t_email_send_at')->nullable();
            $table->timestamp('s_email_send_at')->nullable();
            $table->string('provider')->nullable(); // Social Login
            $table->string('provider_id')->nullable(); // Social Login
            $table->text('custom_fields')->nullable();
            $table->tinyInteger('owner')->default(0);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('users');
    }
}
