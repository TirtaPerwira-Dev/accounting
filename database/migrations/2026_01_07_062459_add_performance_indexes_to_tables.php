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
        // Indexes for journals table
        Schema::table('journals', function (Blueprint $table) {
            $table->index(['status', 'transaction_date'], 'idx_journals_status_date');
            $table->index(['status', 'created_at'], 'idx_journals_status_created');
            $table->index('transaction_type', 'idx_journals_type');
        });

        // Indexes for journal_details table
        Schema::table('journal_details', function (Blueprint $table) {
            $table->index('nomor_bantu_id', 'idx_jd_nomor_bantu');
            $table->index(['journal_id', 'nomor_bantu_id'], 'idx_jd_journal_nomor');
        });

        // Indexes for rekenings table
        Schema::table('rekenings', function (Blueprint $table) {
            $table->index('no_rek', 'idx_rekenings_no_rek');
            $table->index(['kelompok_id', 'no_rek'], 'idx_rekenings_kelompok');
        });

        // Indexes for kelompoks table
        Schema::table('kelompoks', function (Blueprint $table) {
            $table->index('no_kel', 'idx_kelompoks_no_kel');
        });

        // Indexes for nomor_bantus table
        Schema::table('nomor_bantus', function (Blueprint $table) {
            $table->index(['rekening_id', 'no_bantu'], 'idx_nb_rekening_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->dropIndex('idx_journals_status_date');
            $table->dropIndex('idx_journals_status_created');
            $table->dropIndex('idx_journals_type');
        });

        Schema::table('journal_details', function (Blueprint $table) {
            $table->dropIndex('idx_jd_nomor_bantu');
            $table->dropIndex('idx_jd_journal_nomor');
        });

        Schema::table('rekenings', function (Blueprint $table) {
            $table->dropIndex('idx_rekenings_no_rek');
            $table->dropIndex('idx_rekenings_kelompok');
        });

        Schema::table('kelompoks', function (Blueprint $table) {
            $table->dropIndex('idx_kelompoks_no_kel');
        });

        Schema::table('nomor_bantus', function (Blueprint $table) {
            $table->dropIndex('idx_nb_rekening_no');
        });
    }
};
