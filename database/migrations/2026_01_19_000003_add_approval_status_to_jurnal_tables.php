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
        // Tambah kolom approval_status dan approved_by di semua tabel jurnal
        $tables = [
            'jurnal_rekening_air',
            'jurnal_pemakaian_bahans',
            'jurnal_memorials',
            'jurnal_pembelians',
            'jurnal_bayar_kas_banks',
            'jurnal_penerimaan_kas',
        ];

        foreach ($tables as $table) {
            // Skip if approval_status already exists
            if (!Schema::hasColumn($table, 'approval_status')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->enum('approval_status', ['pending', 'approved', 'rejected'])
                        ->default('pending')
                        ->after('confirmed_by');
                    $table->foreignId('approved_by')->nullable()->after('approval_status')
                        ->constrained('users')->nullOnDelete();
                    $table->timestamp('approved_at')->nullable()->after('approved_by');
                    $table->text('approval_notes')->nullable()->after('approved_at');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'jurnal_rekening_air',
            'jurnal_pemakaian_bahans',
            'jurnal_memorials',
            'jurnal_pembelians',
            'jurnal_bayar_kas_banks',
            'jurnal_penerimaan_kas',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropForeign(['approved_by']);
                $table->dropColumn(['approval_status', 'approved_by', 'approved_at', 'approval_notes']);
            });
        }
    }
};
