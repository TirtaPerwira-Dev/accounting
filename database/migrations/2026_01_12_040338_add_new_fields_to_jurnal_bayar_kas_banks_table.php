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
            $table->string('no_voucher')->nullable()->after('id');
            $table->date('tanggal_check')->nullable()->after('tanggal');
            $table->string('nama_bank')->nullable()->after('rekening_id');
            $table->string('no_cek')->nullable()->after('nama_bank');
            $table->string('beban_bagian')->nullable()->after('no_cek');
            $table->string('dibayar_kepada')->nullable()->after('beban_bagian');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jurnal_bayar_kas_banks', function (Blueprint $table) {
            $table->dropColumn([
                'no_voucher',
                'tanggal_check',
                'nama_bank',
                'no_cek',
                'beban_bagian',
                'dibayar_kepada',
            ]);
        });
    }
};
