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
        Schema::table('jurnal_penerimaan_kas', function (Blueprint $table) {
            // Rename detail_items to detail_penerimaan
            $table->renameColumn('detail_items', 'detail_penerimaan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jurnal_penerimaan_kas', function (Blueprint $table) {
            // Rename back detail_penerimaan to detail_items
            $table->renameColumn('detail_penerimaan', 'detail_items');
        });
    }
};
