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
        Schema::table('jurnal_penerimaan_kas_details', function (Blueprint $table) {
            $table->text('keterangan_item')->nullable()->after('jumlah');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jurnal_penerimaan_kas_details', function (Blueprint $table) {
            $table->dropColumn('keterangan_item');
        });
    }
};
