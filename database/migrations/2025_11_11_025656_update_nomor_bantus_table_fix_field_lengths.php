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
        Schema::table('nomor_bantus', function (Blueprint $table) {
            // Perbesar field no_bantu dari varchar(2) ke varchar(3) untuk menampung nomor 3 digit
            $table->string('no_bantu', 3)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nomor_bantus', function (Blueprint $table) {
            // Kembalikan ke varchar(2)
            $table->string('no_bantu', 2)->change();
        });
    }
};
