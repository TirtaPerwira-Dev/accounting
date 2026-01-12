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
        // Ubah kolom data menjadi TEXT di jurnal_bayar_kas_banks
        Schema::table('jurnal_bayar_kas_banks', function (Blueprint $table) {
            $table->text('data')->nullable()->change();
        });

        // Ubah kolom data menjadi TEXT di jurnal_memorials
        Schema::table('jurnal_memorials', function (Blueprint $table) {
            $table->text('data')->nullable()->change();
        });

        // Tambahkan kolom data di jurnal_pemakaian_bahans
        Schema::table('jurnal_pemakaian_bahans', function (Blueprint $table) {
            $table->text('data')->nullable()->after('is_confirmed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan ke varchar di jurnal_bayar_kas_banks
        Schema::table('jurnal_bayar_kas_banks', function (Blueprint $table) {
            $table->string('data')->nullable()->change();
        });

        // Kembalikan ke varchar di jurnal_memorials
        Schema::table('jurnal_memorials', function (Blueprint $table) {
            $table->string('data')->nullable()->change();
        });

        // Hapus kolom data di jurnal_pemakaian_bahans
        Schema::table('jurnal_pemakaian_bahans', function (Blueprint $table) {
            $table->dropColumn('data');
        });
    }
};
