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
        Schema::table('jurnal_pemakaian_bahans', function (Blueprint $table) {
            $table->dropUnique('jurnal_pemakaian_bahans_no_reff_unique');
        });

        Schema::table('jurnal_memorials', function (Blueprint $table) {
            $table->dropUnique('jurnal_memorials_no_reff_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jurnal_pemakaian_bahans', function (Blueprint $table) {
            $table->unique('no_reff');
        });

        Schema::table('jurnal_memorials', function (Blueprint $table) {
            $table->unique('no_reff');
        });
    }
};
