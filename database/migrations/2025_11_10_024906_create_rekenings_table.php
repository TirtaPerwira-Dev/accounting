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
        Schema::create('rekenings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelompok_id')->constrained('kelompoks')->onDelete('cascade');
            $table->string('no_rek', 4)->comment('Nomor Rekening (1101, 1102, dst)');
            $table->string('nama_rek')->comment('Nama Rekening (Kas, Bank, dst)');
            $table->enum('kode', ['D', 'K'])->comment('Kode Debit/Kredit');
            $table->enum('kel', ['1', '2', '3', '4', '5', '6'])->comment('KEL induk rekening sesuai kelompok');
            $table->string('data', 10)->nullable()->comment('Data tambahan SAKEP (contoh: AT)');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['kelompok_id', 'no_rek']);
            $table->index(['kelompok_id', 'kode']);
            $table->index(['kelompok_id', 'kel']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekenings');
    }
};
