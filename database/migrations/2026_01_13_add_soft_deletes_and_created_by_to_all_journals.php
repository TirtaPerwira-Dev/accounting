<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add soft deletes and created_by to all journal tables
     */
    public function up(): void
    {
        // Add to jurnal_rekening_air
        Schema::table('jurnal_rekening_air', function (Blueprint $table) {
            if (!Schema::hasColumn('jurnal_rekening_air', 'deleted_at')) {
                $table->softDeletes();
            }
            if (!Schema::hasColumn('jurnal_rekening_air', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('company_id');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            }
        });

        // Add to jurnal_penerimaan_kas
        Schema::table('jurnal_penerimaan_kas', function (Blueprint $table) {
            if (!Schema::hasColumn('jurnal_penerimaan_kas', 'deleted_at')) {
                $table->softDeletes();
            }
            if (!Schema::hasColumn('jurnal_penerimaan_kas', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('reff');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('jurnal_penerimaan_kas', 'is_confirmed')) {
                $table->boolean('is_confirmed')->default(false)->after('created_by');
                $table->unsignedBigInteger('confirmed_by')->nullable()->after('is_confirmed');
                $table->timestamp('confirmed_at')->nullable()->after('confirmed_by');
                $table->foreign('confirmed_by')->references('id')->on('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('jurnal_penerimaan_kas', 'company_id')) {
                $table->unsignedBigInteger('company_id')->default(1)->after('reff');
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            }
        });

        // Add to jurnal_pembelians
        Schema::table('jurnal_pembelians', function (Blueprint $table) {
            if (!Schema::hasColumn('jurnal_pembelians', 'deleted_at')) {
                $table->softDeletes();
            }
            if (!Schema::hasColumn('jurnal_pembelians', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('company_id');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            }
        });

        // Add to jurnal_memorials
        Schema::table('jurnal_memorials', function (Blueprint $table) {
            if (!Schema::hasColumn('jurnal_memorials', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Add to jurnal_bayar_kas_banks
        Schema::table('jurnal_bayar_kas_banks', function (Blueprint $table) {
            if (!Schema::hasColumn('jurnal_bayar_kas_banks', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Add to jurnal_pemakaian_bahans
        Schema::table('jurnal_pemakaian_bahans', function (Blueprint $table) {
            if (!Schema::hasColumn('jurnal_pemakaian_bahans', 'deleted_at')) {
                $table->softDeletes();
            }
            if (!Schema::hasColumn('jurnal_pemakaian_bahans', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('company_id');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jurnal_rekening_air', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropForeign(['created_by']);
            $table->dropColumn('created_by');
        });

        Schema::table('jurnal_penerimaan_kas', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropForeign(['created_by', 'confirmed_by', 'company_id']);
            $table->dropColumn(['created_by', 'is_confirmed', 'confirmed_by', 'confirmed_at', 'company_id']);
        });

        Schema::table('jurnal_pembelians', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropForeign(['created_by']);
            $table->dropColumn('created_by');
        });

        Schema::table('jurnal_memorials', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('jurnal_bayar_kas_banks', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('jurnal_pemakaian_bahans', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropForeign(['created_by']);
            $table->dropColumn('created_by');
        });
    }
};
