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
        Schema::table('jurnal_pembelians', function (Blueprint $table) {
            if (!Schema::hasColumn('jurnal_pembelians', 'lampiran')) {
                $table->string('lampiran')->nullable()->after('keterangan');
            }
        });

        Schema::table('jurnal_rekening_air', function (Blueprint $table) {
            if (!Schema::hasColumn('jurnal_rekening_air', 'lampiran')) {
                $table->string('lampiran')->nullable()->after('keterangan');
            }
            if (!Schema::hasColumn('jurnal_rekening_air', 'total_item_input')) {
                $table->unsignedInteger('total_item_input')->nullable()->after('lampiran');
            }
            if (!Schema::hasColumn('jurnal_rekening_air', 'nominal_input')) {
                $table->decimal('nominal_input', 15, 2)->nullable()->after('total_item_input');
            }
        });

        Schema::table('jurnal_penerimaan_kas', function (Blueprint $table) {
            if (!Schema::hasColumn('jurnal_penerimaan_kas', 'lampiran')) {
                $table->string('lampiran')->nullable()->after('keterangan');
            }
            if (!Schema::hasColumn('jurnal_penerimaan_kas', 'total_item_input')) {
                $table->unsignedInteger('total_item_input')->nullable()->after('lampiran');
            }
            if (!Schema::hasColumn('jurnal_penerimaan_kas', 'nominal_input')) {
                $table->decimal('nominal_input', 15, 2)->nullable()->after('total_item_input');
            }
        });

        Schema::table('jurnal_bayar_kas_banks', function (Blueprint $table) {
            if (!Schema::hasColumn('jurnal_bayar_kas_banks', 'lampiran')) {
                $table->string('lampiran')->nullable()->after('keterangan');
            }
        });

        Schema::table('jurnal_pemakaian_bahans', function (Blueprint $table) {
            if (!Schema::hasColumn('jurnal_pemakaian_bahans', 'lampiran')) {
                $table->string('lampiran')->nullable()->after('keterangan');
            }
        });

        Schema::table('jurnal_memorials', function (Blueprint $table) {
            if (!Schema::hasColumn('jurnal_memorials', 'lampiran')) {
                $table->string('lampiran')->nullable()->after('keterangan');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jurnal_memorials', function (Blueprint $table) {
            if (Schema::hasColumn('jurnal_memorials', 'lampiran')) {
                $table->dropColumn('lampiran');
            }
        });

        Schema::table('jurnal_pemakaian_bahans', function (Blueprint $table) {
            if (Schema::hasColumn('jurnal_pemakaian_bahans', 'lampiran')) {
                $table->dropColumn('lampiran');
            }
        });

        Schema::table('jurnal_bayar_kas_banks', function (Blueprint $table) {
            if (Schema::hasColumn('jurnal_bayar_kas_banks', 'lampiran')) {
                $table->dropColumn('lampiran');
            }
        });

        Schema::table('jurnal_penerimaan_kas', function (Blueprint $table) {
            $dropColumns = [];

            if (Schema::hasColumn('jurnal_penerimaan_kas', 'nominal_input')) {
                $dropColumns[] = 'nominal_input';
            }
            if (Schema::hasColumn('jurnal_penerimaan_kas', 'total_item_input')) {
                $dropColumns[] = 'total_item_input';
            }
            if (Schema::hasColumn('jurnal_penerimaan_kas', 'lampiran')) {
                $dropColumns[] = 'lampiran';
            }

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });

        Schema::table('jurnal_rekening_air', function (Blueprint $table) {
            $dropColumns = [];

            if (Schema::hasColumn('jurnal_rekening_air', 'nominal_input')) {
                $dropColumns[] = 'nominal_input';
            }
            if (Schema::hasColumn('jurnal_rekening_air', 'total_item_input')) {
                $dropColumns[] = 'total_item_input';
            }
            if (Schema::hasColumn('jurnal_rekening_air', 'lampiran')) {
                $dropColumns[] = 'lampiran';
            }

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });

        Schema::table('jurnal_pembelians', function (Blueprint $table) {
            if (Schema::hasColumn('jurnal_pembelians', 'lampiran')) {
                $table->dropColumn('lampiran');
            }
        });
    }
};
