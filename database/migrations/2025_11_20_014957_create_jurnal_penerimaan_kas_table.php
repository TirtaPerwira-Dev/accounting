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
        Schema::create('jurnal_penerimaan_kas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kas_bank_id'); // Nomor bantu untuk kas/bank
            $table->date('tanggal');
            $table->string('nomor_bukti', 50);
            $table->text('keterangan');
            $table->unsignedBigInteger('kode_proyek_id')->nullable();
            $table->unsignedBigInteger('nomor_rekening_id'); // Nomor rekening
            $table->decimal('jumlah', 15, 2);
            $table->string('reff', 10)->default('3');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('kas_bank_id')->references('id')->on('nomor_bantus')->onDelete('restrict');
            $table->foreign('kode_proyek_id')->references('id')->on('kode_proyeks')->onDelete('set null');
            $table->foreign('nomor_rekening_id')->references('id')->on('rekenings')->onDelete('restrict');

            // Indexes
            $table->index('tanggal');
            $table->index('nomor_bukti');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jurnal_penerimaan_kas');
    }
};
