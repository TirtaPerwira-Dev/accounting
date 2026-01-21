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
        // 1. Hapus approval_status, approved_by, approved_at dari jurnal_pembelians (jika ada)
        Schema::table('jurnal_pembelians', function (Blueprint $table) {
            // Drop foreign keys first
            $columns = Schema::getColumnListing('jurnal_pembelians');

            if (in_array('approved_by', $columns)) {
                try {
                    $table->dropForeign(['approved_by']);
                } catch (\Exception $e) {
                }
                $table->dropColumn('approved_by');
            }
            if (in_array('approval_status', $columns)) {
                $table->dropColumn('approval_status');
            }
            if (in_array('approved_at', $columns)) {
                $table->dropColumn('approved_at');
            }
        });

        // 2. Hapus approval_status, approved_by, approved_at dari jurnal_rekening_air (jika ada)
        Schema::table('jurnal_rekening_air', function (Blueprint $table) {
            $columns = Schema::getColumnListing('jurnal_rekening_air');

            if (in_array('approved_by', $columns)) {
                try {
                    $table->dropForeign(['approved_by']);
                } catch (\Exception $e) {
                }
                $table->dropColumn('approved_by');
            }
            if (in_array('approval_status', $columns)) {
                $table->dropColumn('approval_status');
            }
            if (in_array('approved_at', $columns)) {
                $table->dropColumn('approved_at');
            }
            if (in_array('approval_notes', $columns)) {
                $table->dropColumn('approval_notes');
            }
        });

        // 5. Tambahkan deleted_by ke semua tabel jurnal yang sudah ada soft deletes
        $tables = [
            'jurnal_pembelians',
            'jurnal_rekening_air',
            'jurnal_penerimaan_kas',
            'jurnal_bayar_kas_banks',
            'jurnal_pemakaian_bahans',
            'jurnal_memorials',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $columns = Schema::getColumnListing($tableName);
                    if (in_array('deleted_at', $columns) && !in_array('deleted_by', $columns)) {
                        $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
                        $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback approval columns
        Schema::table('jurnal_pembelians', function (Blueprint $table) {
            $table->string('approval_status')->default('pending')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
        });

        Schema::table('jurnal_rekening_air', function (Blueprint $table) {
            $table->string('approval_status')->default('pending')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
        });

        // Rollback unique constraint
        Schema::table('jurnal_rekening_air', function (Blueprint $table) {
            $table->dropIndex(['no_reff']);
            $table->unique('no_reff');
        });

        // Rollback deleted_by
        $allTables = [
            'jurnal_pembelians',
            'jurnal_rekening_air',
            'jurnal_penerimaan_kas',
            'jurnal_bayar_kas_banks',
            'jurnal_pemakaian_bahans',
            'jurnal_memorials',
            'jurnal_pembelian_details',
            'jurnal_rekening_air_details',
            'jurnal_penerimaan_kas_details',
        ];

        foreach ($allTables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (Schema::hasColumn($tableName, 'deleted_by')) {
                        $table->dropForeign([$tableName . '_deleted_by_foreign']);
                        $table->dropColumn('deleted_by');
                    }
                });
            }
        }
    }
};
