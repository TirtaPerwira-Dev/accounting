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
        Schema::create('jurnal_bayar_kas_bank_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jurnal_bayar_kas_bank_id')
                ->constrained('jurnal_bayar_kas_banks')
                ->onDelete('cascade')
                ->comment('FK ke jurnal_bayar_kas_banks (header)');

            // Detail item pembayaran
            $table->string('no_voucher')->nullable()->comment('Nomor voucher pembayaran');
            $table->text('keterangan')->nullable()->comment('Keterangan item pembayaran');
            $table->decimal('jumlah', 15, 2)->comment('Jumlah dalam rupiah');
            $table->string('dibayar_kepada')->nullable()->comment('Nama penerima pembayaran');

            // Akun (bisa debit atau kredit tergantung posisi)
            $table->foreignId('kelompok_id')->nullable()->constrained('kelompoks')->onDelete('restrict');
            $table->foreignId('rekening_id')->nullable()->constrained('rekenings')->onDelete('restrict');
            $table->foreignId('nomor_bantu_id')->nullable()->constrained('nomor_bantus')->onDelete('restrict');
            
            // Kode proyek (opsional)
            $table->foreignId('kode_proyek_id')->nullable()->constrained('kode_proyeks')->onDelete('set null');

            $table->timestamps();

            // Indexes
            $table->index('jurnal_bayar_kas_bank_id', 'jbkbd_header_idx');
            $table->index(['kelompok_id', 'rekening_id', 'nomor_bantu_id'], 'jbkbd_k_r_nb_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jurnal_bayar_kas_bank_details');
    }
};
