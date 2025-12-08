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
        Schema::create('jurnal_penerimaan_kas_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jurnal_penerimaan_kas_id')
                ->constrained('jurnal_penerimaan_kas')
                ->onDelete('cascade');
            $table->foreignId('kelompok_id')->nullable()->constrained('kelompoks');
            $table->foreignId('rekening_id')->nullable()->constrained('rekenings');
            $table->foreignId('nomor_bantu_id')->nullable()->constrained('nomor_bantus');
            $table->foreignId('kode_proyek_id')->nullable()->constrained('kode_proyeks');
            $table->string('nomor_bukti')->nullable();
            $table->decimal('jumlah', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jurnal_penerimaan_kas_details');
    }
};
