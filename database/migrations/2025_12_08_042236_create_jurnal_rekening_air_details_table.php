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
        Schema::create('jurnal_rekening_air_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jurnal_rekening_air_id')->constrained('jurnal_rekening_air')->onDelete('cascade');
            $table->foreignId('kelompok_id')->nullable()->constrained('kelompoks')->onDelete('set null');
            $table->foreignId('rekening_id')->constrained('rekenings')->onDelete('cascade');
            $table->foreignId('nomor_bantu_id')->nullable()->constrained('nomor_bantus')->onDelete('set null');
            $table->foreignId('kode_proyek_id')->nullable()->constrained('kode_proyeks')->onDelete('set null');
            $table->enum('position', ['debit', 'kredit'])->default('debit');
            $table->decimal('jumlah', 15, 2)->default(0);
            $table->timestamps();

            // Indexes untuk performa
            $table->index('jurnal_rekening_air_id');
            $table->index('rekening_id');
            $table->index('position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jurnal_rekening_air_details');
    }
};
