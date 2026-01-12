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
        Schema::create('saldo_awal_jurnal', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_jurnal'); // rekening_air, pemakaian_bahan, memorial, dll
            $table->year('tahun'); // tahun saldo awal
            $table->decimal('saldo_debit', 20, 2)->default(0);
            $table->decimal('saldo_kredit', 20, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->timestamps();

            // Index untuk pencarian cepat
            $table->index(['jenis_jurnal', 'tahun']);
            
            // Unique constraint: satu jenis jurnal hanya bisa punya satu saldo awal per tahun
            $table->unique(['jenis_jurnal', 'tahun']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saldo_awal_jurnal');
    }
};
