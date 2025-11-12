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
        Schema::create('nomor_bantus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rekening_id')->constrained('rekenings')->onDelete('cascade');
            $table->string('no_bantu', 2)->comment('Nomor Bantu (10, 20, 30, dst)');
            $table->string('nm_bantu')->comment('Nama Bantu (Bank BPD, BMT Mrebet, dst)');
            $table->enum('kel', ['1', '2', '3', '4', '5', '6'])->comment('KEL (1-6)');
            $table->enum('kode', ['D', 'K'])->comment('Kode Debit/Kredit');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['rekening_id', 'no_bantu']);
            $table->index(['rekening_id', 'kel']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nomor_bantus');
    }
};
