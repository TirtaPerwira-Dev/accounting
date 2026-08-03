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
        Schema::table('rekenings', function (Blueprint $table) {
            // Add kel field for rekening grouping (same as kelompok)
            if (!Schema::hasColumn('rekenings', 'kel')) {
                $table->enum('kel', ['1', '2', '3', '4', '5', '6'])
                    ->nullable()
                    ->after('kode')
                    ->comment('KEL (1-6) - sama dengan kelompok');
            }

            // Add data field if not exists (should already exist from previous migrations)
            if (!Schema::hasColumn('rekenings', 'data')) {
                $table->string('data', 10)
                    ->nullable()
                    ->after('kel')
                    ->comment('DATA field from SAKEP');
            }

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rekenings', function (Blueprint $table) {
            if (Schema::hasColumn('rekenings', 'kel')) {
                $table->dropColumn('kel');
            }
        });
    }
};
