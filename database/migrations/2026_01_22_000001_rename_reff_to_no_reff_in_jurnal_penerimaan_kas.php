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
            if (Schema::hasColumn('jurnal_penerimaan_kas', 'reff')) {
                $table->renameColumn('reff', 'no_reff');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jurnal_penerimaan_kas', function (Blueprint $table) {
            if (Schema::hasColumn('jurnal_penerimaan_kas', 'no_reff')) {
                $table->renameColumn('no_reff', 'reff');
            }
        });
    }
};
