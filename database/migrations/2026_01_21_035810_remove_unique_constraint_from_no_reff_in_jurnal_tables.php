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
        // Hapus unique constraint dari no_reff jika ada
        $tables = [
            'jurnal_rekening_air',
            'jurnal_bayar_kas_banks',
            'jurnal_pemakaian_bahans',
            'jurnal_memorials',
        ];

        foreach ($tables as $table) {
            try {
                DB::statement("ALTER TABLE {$table} DROP INDEX {$table}_no_reff_unique");
            } catch (\Exception $e) {
                // Ignore jika index tidak ada
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan unique constraint
        Schema::table('jurnal_rekening_air', function (Blueprint $table) {
            $table->unique('no_reff');
        });
    }
};
