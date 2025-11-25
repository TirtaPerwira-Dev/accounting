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
        Schema::table('jurnal_rekening_air', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['kelompok_kredit_id']);
            $table->dropForeign(['rekening_kredit_id']);
            $table->dropForeign(['nomor_bantu_kredit_id']);
            $table->dropForeign(['kode_proyek_id']);

            // Drop columns that are no longer needed
            $table->dropColumn([
                'kelompok_kredit_id',
                'rekening_kredit_id',
                'nomor_bantu_kredit_id',
                'nama_nomor_bantu_kredit',
                'data_k',
                'kode_proyek_id'
            ]);

            // Keep only basic fields + JSON
            // tanggal, bukti, keterangan, rp, rekening_air_items already exist
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jurnal_rekening_air', function (Blueprint $table) {
            // Restore dropped columns
            $table->unsignedBigInteger('kelompok_kredit_id')->comment('Kelompok akun kredit');
            $table->unsignedBigInteger('rekening_kredit_id')->comment('Rekening akun kredit');
            $table->unsignedBigInteger('nomor_bantu_kredit_id')->comment('Nomor bantu akun kredit');
            $table->char('data_k', 1)->nullable()->comment('Debit/Kredit dari tabel rekening (untuk validasi)');
            $table->string('nama_nomor_bantu_kredit')->nullable()->comment('Nama nomor bantu kredit (manual input)');
            $table->unsignedBigInteger('kode_proyek_id')->nullable()->comment('Kode proyek (opsional)');

            // Restore foreign keys
            $table->foreign('kelompok_kredit_id')->references('id')->on('kelompoks')->onDelete('restrict');
            $table->foreign('rekening_kredit_id')->references('id')->on('rekenings')->onDelete('restrict');
            $table->foreign('nomor_bantu_kredit_id')->references('id')->on('nomor_bantus')->onDelete('restrict');
            $table->foreign('kode_proyek_id')->references('id')->on('kode_proyeks')->onDelete('set null');
        });
    }
};
