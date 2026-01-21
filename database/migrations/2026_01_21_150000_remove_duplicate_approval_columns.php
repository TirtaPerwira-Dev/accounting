<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menghapus kolom approval yang duplikat karena sudah ada is_confirmed, confirmed_by, confirmed_at
     * Kolom yang dihapus: approval_status, approved_by, approved_at, approval_notes
     */
    public function up(): void
    {
        $tables = [
            'jurnal_penerimaan_kas',
            'jurnal_bayar_kas_banks',
            'jurnal_memorials'
        ];

        foreach ($tables as $tableName) {
            // Drop foreign key constraint for approved_by if exists
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND COLUMN_NAME = 'approved_by'
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ", [$tableName]);

            foreach ($foreignKeys as $fk) {
                Schema::table($tableName, function (Blueprint $table) use ($fk) {
                    $table->dropForeign($fk->CONSTRAINT_NAME);
                });
            }

            // Now drop columns
            $columnsToRemove = ['approval_status', 'approved_by', 'approved_at', 'approval_notes'];
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn($tableName, $column)) {
                    Schema::table($tableName, function (Blueprint $table) use ($column) {
                        $table->dropColumn($column);
                    });
                }
            }
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
            'jurnal_memorials'
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'approval_status')) {
                    $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending')->after('confirmed_by');
                }
                if (!Schema::hasColumn($tableName, 'approved_by')) {
                    $table->unsignedBigInteger('approved_by')->nullable()->after('approval_status');
                }
                if (!Schema::hasColumn($tableName, 'approved_at')) {
                    $table->timestamp('approved_at')->nullable()->after('approved_by');
                }
                if (!Schema::hasColumn($tableName, 'approval_notes')) {
                    $table->text('approval_notes')->nullable()->after('approved_at');
                }
            });
        }
    }
};
