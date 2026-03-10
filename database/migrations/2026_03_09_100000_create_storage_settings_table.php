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
        Schema::create('storage_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained('business')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('disk', 20)->default('public');
            $table->string('visibility', 20)->default('public');
            $table->string('directory')->default('attachments');
            $table->unsignedInteger('signed_url_ttl')->default(10);
            $table->timestamps();

            $table->unique('business_id');
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('storage_settings');
    }
};
