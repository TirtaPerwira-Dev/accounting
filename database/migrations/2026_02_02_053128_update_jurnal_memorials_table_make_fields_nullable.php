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
        Schema::table('jurnal_memorials', function (Blueprint $table) {
            // Drop foreign keys terlebih dahulu
            $table->dropForeign(['kelompok_id']);
            $table->dropForeign(['rekening_id']);
            
            // Ubah kolom menjadi nullable
            $table->unsignedBigInteger('kelompok_id')->nullable()->change();
            $table->unsignedBigInteger('rekening_id')->nullable()->change();
            $table->enum('kode', ['D', 'K'])->nullable()->change();
            
            // Tambahkan kembali foreign keys
            $table->foreign('kelompok_id')->references('id')->on('kelompoks')->onDelete('restrict');
            $table->foreign('rekening_id')->references('id')->on('rekenings')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jurnal_memorials', function (Blueprint $table) {
            // Drop foreign keys
            $table->dropForeign(['kelompok_id']);
            $table->dropForeign(['rekening_id']);
            
            // Ubah kembali menjadi NOT NULL
            $table->unsignedBigInteger('kelompok_id')->nullable(false)->change();
            $table->unsignedBigInteger('rekening_id')->nullable(false)->change();
            $table->enum('kode', ['D', 'K'])->nullable(false)->change();
            
            // Tambahkan kembali foreign keys
            $table->foreign('kelompok_id')->references('id')->on('kelompoks')->onDelete('restrict');
            $table->foreign('rekening_id')->references('id')->on('rekenings')->onDelete('restrict');
        });
    }
};
