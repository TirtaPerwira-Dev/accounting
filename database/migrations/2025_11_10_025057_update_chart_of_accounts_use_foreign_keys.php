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
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            // Remove the individual SAKEP fields we added earlier
            if (Schema::hasColumn('chart_of_accounts', 'no_kel')) {
                $table->dropColumn(['no_kel', 'no_rek', 'no_bantu', 'nama_kel', 'kel', 'kode']);
            }

            // Add foreign keys to the new tables
            $table->foreignId('kelompok_id')->nullable()->after('company_id')->constrained('kelompoks')->nullOnDelete();
            $table->foreignId('rekening_id')->nullable()->after('kelompok_id')->constrained('rekenings')->nullOnDelete();
            $table->foreignId('nomor_bantu_id')->nullable()->after('rekening_id')->constrained('nomor_bantus')->nullOnDelete();

            // Add indexes
            $table->index(['company_id', 'kelompok_id']);
            $table->index(['company_id', 'rekening_id']);
            $table->index(['company_id', 'nomor_bantu_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropForeign(['kelompok_id']);
            $table->dropForeign(['rekening_id']);
            $table->dropForeign(['nomor_bantu_id']);

            $table->dropIndex(['company_id', 'kelompok_id']);
            $table->dropIndex(['company_id', 'rekening_id']);
            $table->dropIndex(['company_id', 'nomor_bantu_id']);

            $table->dropColumn(['kelompok_id', 'rekening_id', 'nomor_bantu_id']);
        });
    }
};
