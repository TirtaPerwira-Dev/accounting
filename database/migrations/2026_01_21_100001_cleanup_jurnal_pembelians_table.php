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
     * Cleanup jurnal_pembelians table:
     * 1. Remove redundant columns (bukti_item, keterangan_item, jumlah_item)
     * 2. Remove unused columns (approval_notes, pembelian_items)
     * 3. Remove denormalized columns (kelompok_kredit_id, rekening_kredit_id, kelompok_debit_id, rekening_debit_id)
     *    - These can be derived from nomor_bantu via relationships
     * 4. Keep nama_nomor_bantu_kredit for display purposes (denormalized for performance)
     */
    public function up(): void
    {
        // Drop foreign keys first (if they exist)
        $foreignKeys = [
            'jurnal_pembelians_kelompok_kredit_id_foreign',
            'jurnal_pembelians_rekening_kredit_id_foreign',
            'jurnal_pembelians_kelompok_debit_id_foreign',
            'jurnal_pembelians_rekening_debit_id_foreign',
        ];

        foreach ($foreignKeys as $fk) {
            try {
                Schema::table('jurnal_pembelians', function (Blueprint $table) use ($fk) {
                    $table->dropForeign($fk);
                });
            } catch (\Exception $e) {
                // Foreign key might not exist, continue
            }
        }

        // Remove redundant and unused columns
        $columnsToRemove = [
            'bukti_item',
            'keterangan_item',
            'jumlah_item',
            'pembelian_items',
            'approval_notes',
            'kelompok_kredit_id',
            'rekening_kredit_id',
            'kelompok_debit_id',
            'rekening_debit_id',
        ];

        foreach ($columnsToRemove as $column) {
            if (Schema::hasColumn('jurnal_pembelians', $column)) {
                Schema::table('jurnal_pembelians', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jurnal_pembelians', function (Blueprint $table) {
            // Re-add removed columns
            if (!Schema::hasColumn('jurnal_pembelians', 'bukti_item')) {
                $table->string('bukti_item')->nullable();
            }
            if (!Schema::hasColumn('jurnal_pembelians', 'keterangan_item')) {
                $table->text('keterangan_item')->nullable();
            }
            if (!Schema::hasColumn('jurnal_pembelians', 'jumlah_item')) {
                $table->decimal('jumlah_item', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('jurnal_pembelians', 'pembelian_items')) {
                $table->json('pembelian_items')->nullable();
            }
            if (!Schema::hasColumn('jurnal_pembelians', 'approval_notes')) {
                $table->text('approval_notes')->nullable();
            }

            // Re-add denormalized columns
            if (!Schema::hasColumn('jurnal_pembelians', 'kelompok_kredit_id')) {
                $table->unsignedBigInteger('kelompok_kredit_id')->nullable();
            }
            if (!Schema::hasColumn('jurnal_pembelians', 'rekening_kredit_id')) {
                $table->unsignedBigInteger('rekening_kredit_id')->nullable();
            }
            if (!Schema::hasColumn('jurnal_pembelians', 'kelompok_debit_id')) {
                $table->unsignedBigInteger('kelompok_debit_id')->nullable();
            }
            if (!Schema::hasColumn('jurnal_pembelians', 'rekening_debit_id')) {
                $table->unsignedBigInteger('rekening_debit_id')->nullable();
            }
        });
    }
};
