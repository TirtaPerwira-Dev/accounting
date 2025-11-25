<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menambahkan referensi langsung ke SAKEP hierarchy di opening_balances
     */
    public function up(): void
    {
        Schema::table('opening_balances', function (Blueprint $table) {
            // Add direct SAKEP references
            $table->foreignId('kelompok_id')->nullable()->constrained('kelompoks')->onDelete('cascade');
            $table->foreignId('rekening_id')->nullable()->constrained('rekenings')->onDelete('cascade');
            $table->foreignId('nomor_bantu_id')->nullable()->constrained('nomor_bantus')->onDelete('cascade');

            // Keep existing account_id for backward compatibility but make it nullable
            $table->foreignId('account_id')->nullable()->change();

            // Add index for better performance
            $table->index(['kelompok_id', 'rekening_id', 'nomor_bantu_id'], 'opening_balances_sakep_index');
            $table->index(['company_id', 'nomor_bantu_id'], 'opening_balances_company_nomor_bantu_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('opening_balances', function (Blueprint $table) {
            $table->dropIndex('opening_balances_company_nomor_bantu_index');
            $table->dropIndex('opening_balances_sakep_index');
            $table->dropForeign(['nomor_bantu_id']);
            $table->dropForeign(['rekening_id']);
            $table->dropForeign(['kelompok_id']);
            $table->dropColumn(['kelompok_id', 'rekening_id', 'nomor_bantu_id']);

            // Restore account_id as required
            $table->foreignId('account_id')->nullable(false)->change();
        });
    }
};
