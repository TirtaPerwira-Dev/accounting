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
        Schema::create('jurnal_memorial_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jurnal_memorial_id')
                ->constrained('jurnal_memorials')
                ->onDelete('cascade')
                ->comment('FK ke jurnal_memorials (header)');

            // Detail item memorial
            $table->string('bukti')->nullable()->comment('Nomor bukti memorial');
            $table->text('keterangan')->nullable()->comment('Keterangan item memorial');
            $table->decimal('jumlah', 15, 2)->comment('Jumlah dalam rupiah');
            $table->enum('posisi', ['D', 'K'])->comment('Posisi: D=Debit, K=Kredit');

            // Akun
            $table->foreignId('kelompok_id')->nullable()->constrained('kelompoks')->onDelete('restrict');
            $table->foreignId('rekening_id')->nullable()->constrained('rekenings')->onDelete('restrict');
            $table->foreignId('nomor_bantu_id')->nullable()->constrained('nomor_bantus')->onDelete('restrict');

            // Kode proyek (opsional)
            $table->foreignId('kode_proyek_id')->nullable()->constrained('kode_proyeks')->onDelete('set null');

            $table->timestamps();

            // Indexes
            $table->index('jurnal_memorial_id', 'jmd_header_idx');
            $table->index(['kelompok_id', 'rekening_id', 'nomor_bantu_id'], 'jmd_k_r_nb_idx');
            $table->index('posisi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jurnal_memorial_details');
    }
};
