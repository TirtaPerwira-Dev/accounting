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
        Schema::create('kelompoks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('standard_id')->constrained('accounting_standards')->onDelete('cascade');
            $table->string('no_kel', 2)->comment('Nomor Kelompok (10, 20, 30, dst)');
            $table->string('nama_kel')->comment('Nama Kelompok (Aktiva Lancar, dst)');
            $table->enum('kel', ['1', '2', '3', '4', '5', '6'])->comment('KEL (1-6)');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['standard_id', 'no_kel']);
            $table->index(['standard_id', 'kel']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kelompoks');
    }
};
