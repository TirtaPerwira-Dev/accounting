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
        Schema::create('saldo_awal_rekening', function (Blueprint $table) {
            $table->id();
            $table->year('tahun'); // Tahun saldo awal (misal: 2024, 2025)
            $table->foreignId('rekening_id')->constrained('rekenings')->cascadeOnDelete();
            $table->foreignId('nomor_bantu_id')->nullable()->constrained('nomor_bantus')->nullOnDelete();
            $table->decimal('saldo_awal', 20, 2)->default(0);
            $table->enum('posisi', ['D', 'K'])->default('D'); // D = Debit, K = Kredit
            $table->text('keterangan')->nullable();
            $table->timestamps();

            // Index untuk pencarian cepat
            $table->index(['tahun', 'rekening_id']);
            $table->index(['tahun', 'posisi']);

            // Unique constraint: satu rekening hanya bisa punya satu saldo awal per tahun
            // Jika ada nomor_bantu, kombinasi tahun + rekening + nomor_bantu harus unique
            $table->unique(['tahun', 'rekening_id', 'nomor_bantu_id'], 'unique_saldo_awal_per_rekening');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saldo_awal_rekening');
    }
};
