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
            // Increase reff field from varchar(10) to varchar(50)
            $table->string('reff', 50)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jurnal_penerimaan_kas', function (Blueprint $table) {
            // Revert back to varchar(10)
            $table->string('reff', 10)->change();
        });
    }
};
