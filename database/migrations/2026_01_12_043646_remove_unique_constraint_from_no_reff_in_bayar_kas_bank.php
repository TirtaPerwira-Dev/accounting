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
        Schema::table('jurnal_bayar_kas_banks', function (Blueprint $table) {
            $table->dropUnique('jurnal_bayar_kas_banks_no_reff_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jurnal_bayar_kas_banks', function (Blueprint $table) {
            $table->unique('no_reff');
        });
    }
};
