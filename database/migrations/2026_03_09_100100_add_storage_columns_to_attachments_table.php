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
        Schema::table('attachments', function (Blueprint $table) {
            $table->string('disk', 20)->nullable()->after('file_name');
            $table->string('visibility', 20)->nullable()->after('path');
            $table->string('mime_type')->nullable()->after('visibility');
            $table->unsignedBigInteger('file_size')->nullable()->after('mime_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropColumn(['disk', 'visibility', 'mime_type', 'file_size']);
        });
    }
};
