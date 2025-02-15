<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void{
        Schema::table('business', function (Blueprint $table) {
            $table->bigInteger('business_type_id')->unsigned()->nullable()->change();

            $table->dropForeign(['business_type_id']);
            $table->foreign('business_type_id')->references('id')->on('business_types')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void{
        Schema::table('business', function (Blueprint $table) {
            //
        });
    }
};
