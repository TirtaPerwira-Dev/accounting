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
        Schema::table('jurnal_pembelians', function (Blueprint $table) {
            // Remove single debit account columns
            $table->dropForeign(['kelompok_debit_id']);
            $table->dropForeign(['rekening_debit_id']);
            $table->dropForeign(['nomor_bantu_debit_id']);

            $table->dropColumn([
                'kelompok_debit_id',
                'rekening_debit_id',
                'nomor_bantu_debit_id',
                'data_d'
            ]);

            // Add JSON column for pembelian items (repeater data)
            $table->json('pembelian_items')->nullable()->comment('Data items pembelian dalam format JSON');

            // Add field for nama nomor bantu kredit (manual input)
            $table->string('nama_nomor_bantu_kredit')->nullable()->comment('Nama nomor bantu kredit (manual input)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jurnal_pembelians', function (Blueprint $table) {
            // Remove JSON column
            $table->dropColumn(['pembelian_items', 'nama_nomor_bantu_kredit']);

            // Restore single debit account columns
            $table->unsignedBigInteger('kelompok_debit_id')->comment('Kelompok akun debit');
            $table->unsignedBigInteger('rekening_debit_id')->comment('Rekening akun debit');
            $table->unsignedBigInteger('nomor_bantu_debit_id')->comment('Nomor bantu akun debit');
            $table->char('data_d', 1)->comment('Debit/Kredit dari tabel rekening (untuk validasi)');

            // Restore foreign keys
            $table->foreign('kelompok_debit_id')->references('id')->on('kelompoks')->onDelete('restrict');
            $table->foreign('rekening_debit_id')->references('id')->on('rekenings')->onDelete('restrict');
            $table->foreign('nomor_bantu_debit_id')->references('id')->on('nomor_bantus')->onDelete('restrict');
        });
    }
};
