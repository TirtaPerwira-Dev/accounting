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
        $tables = [
            'jurnal_penerimaan_kas',
            'jurnal_bayar_kas_banks',
            'jurnal_pembelians',
            'jurnal_memorials',
            'jurnal_pemakaian_bahans',
            'jurnal_rekening_air'
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'is_posted')) {
                    $table->boolean('is_posted')->default(false)->after('is_confirmed');
                }
                if (!Schema::hasColumn($tableName, 'posted_at')) {
                    $table->datetime('posted_at')->nullable()->after('is_posted');
                }
                if (!Schema::hasColumn($tableName, 'posted_by')) {
                    $table->foreignId('posted_by')->nullable()->after('posted_at')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn($tableName, 'journal_id')) {
                    $table->foreignId('journal_id')->nullable()->after('posted_by')->constrained('journals')->nullOnDelete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'jurnal_penerimaan_kas',
            'jurnal_bayar_kas_banks',
            'jurnal_pembelians',
            'jurnal_memorials',
            'jurnal_pemakaian_bahans',
            'jurnal_rekening_air'
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['journal_id']);
                $table->dropForeign(['posted_by']);
                $table->dropColumn(['is_posted', 'posted_at', 'posted_by', 'journal_id']);
            });
        }
    }
};
