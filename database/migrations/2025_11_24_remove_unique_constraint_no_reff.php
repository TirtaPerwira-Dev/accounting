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
            // Drop unique constraint pada no_reff karena sekarang akan ada multiple rows per transaksi
            $table->dropUnique(['no_reff']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jurnal_pembelians', function (Blueprint $table) {
            // Restore unique constraint
            $table->unique('no_reff');
        });
    }
};