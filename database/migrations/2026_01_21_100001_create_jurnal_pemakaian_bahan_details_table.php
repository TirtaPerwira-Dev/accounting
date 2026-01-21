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
        Schema::create('jurnal_pemakaian_bahan_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jurnal_pemakaian_bahan_id')
                ->constrained('jurnal_pemakaian_bahans')
                ->onDelete('cascade')
                ->comment('FK ke jurnal_pemakaian_bahans (header)');

            // Detail item pemakaian bahan
            $table->string('bukti')->nullable()->comment('Nomor bukti pemakaian');
            $table->text('keterangan')->nullable()->comment('Keterangan item pemakaian');
            $table->decimal('jumlah', 15, 2)->comment('Jumlah dalam rupiah');
            $table->string('beban_bagian')->nullable()->comment('Bagian yang menanggung beban');

            // Akun debit
            $table->foreignId('kelompok_debit_id')->nullable()->constrained('kelompoks')->onDelete('restrict');
            $table->foreignId('rekening_debit_id')->nullable()->constrained('rekenings')->onDelete('restrict');
            $table->foreignId('nomor_bantu_debit_id')->nullable()->constrained('nomor_bantus')->onDelete('restrict');

            // Akun kredit
            $table->foreignId('kelompok_kredit_id')->nullable()->constrained('kelompoks')->onDelete('restrict');
            $table->foreignId('rekening_kredit_id')->nullable()->constrained('rekenings')->onDelete('restrict');
            $table->foreignId('nomor_bantu_kredit_id')->nullable()->constrained('nomor_bantus')->onDelete('restrict');

            // Kode proyek (opsional)
            $table->foreignId('kode_proyek_id')->nullable()->constrained('kode_proyeks')->onDelete('set null');

            $table->timestamps();

            // Indexes
            $table->index('jurnal_pemakaian_bahan_id', 'jpbd_header_idx');
            $table->index(['kelompok_debit_id', 'rekening_debit_id', 'nomor_bantu_debit_id'], 'jpbd_debit_idx');
            $table->index(['kelompok_kredit_id', 'rekening_kredit_id', 'nomor_bantu_kredit_id'], 'jpbd_kredit_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jurnal_pemakaian_bahan_details');
    }
};
