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
            // Make data_k nullable (boleh kosong)
            $table->char('data_k', 1)->nullable()->change()->comment('Debit/Kredit dari tabel rekening (opsional)');

            // Add data_d column for AT (Aktiva Tetap) values
            $table->char('data_d', 1)->nullable()->after('data_k')->comment('Data untuk rekening AT (Aktiva Tetap)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jurnal_pembelians', function (Blueprint $table) {
            // Restore data_k as NOT NULL
            $table->char('data_k', 1)->nullable(false)->change();

            // Drop data_d column
            $table->dropColumn('data_d');
        });
    }
};
