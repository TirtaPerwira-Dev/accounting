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
        // Buat tabel detail pembelian
        Schema::create('jurnal_pembelian_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('jurnal_pembelian_id')->comment('FK ke jurnal_pembelians (header)');

            // Detail item pembelian
            $table->string('bukti')->nullable()->comment('Nomor bukti transaksi (INV, PO, dll)');
            $table->text('keterangan')->comment('Keterangan item pembelian');
            $table->decimal('jumlah', 15, 2)->comment('Jumlah dalam rupiah');

            // Akun debit (pembelian)
            $table->unsignedBigInteger('kelompok_debit_id')->comment('Kelompok akun debit');
            $table->unsignedBigInteger('rekening_debit_id')->comment('Rekening akun debit');
            $table->unsignedBigInteger('nomor_bantu_debit_id')->comment('Nomor bantu akun debit');

            // Kode proyek (opsional)
            $table->unsignedBigInteger('kode_proyek_id')->nullable()->comment('Kode proyek terkait');

            $table->timestamps();

            // Foreign keys
            $table->foreign('jurnal_pembelian_id')->references('id')->on('jurnal_pembelians')->onDelete('cascade');
            $table->foreign('kelompok_debit_id')->references('id')->on('kelompoks')->onDelete('restrict');
            $table->foreign('rekening_debit_id')->references('id')->on('rekenings')->onDelete('restrict');
            $table->foreign('nomor_bantu_debit_id')->references('id')->on('nomor_bantus')->onDelete('restrict');
            $table->foreign('kode_proyek_id')->references('id')->on('kode_proyeks')->onDelete('set null');

            // Indexes
            $table->index('jurnal_pembelian_id');
            $table->index(
                ['kelompok_debit_id', 'rekening_debit_id', 'nomor_bantu_debit_id'],
                'jpd_k_r_nb_idx'
            );
        });

        // Modifikasi tabel header - hapus kolom JSON
        Schema::table('jurnal_pembelians', function (Blueprint $table) {
            $table->dropColumn('pembelian_items'); // Hapus JSON field
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan kolom JSON
        Schema::table('jurnal_pembelians', function (Blueprint $table) {
            $table->json('pembelian_items')->nullable()->comment('Data items pembelian dalam format JSON');
        });

        // Hapus tabel detail
        Schema::dropIfExists('jurnal_pembelian_details');
    }
};
