<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kelompoks')) {
            Schema::table('kelompoks', function (Blueprint $table) {
                if (Schema::hasColumn('kelompoks', 'no_kel')) {
                    $table->string('no_kel', 2)->change();
                }
            });
        }

        if (Schema::hasTable('rekenings')) {
            Schema::table('rekenings', function (Blueprint $table) {
                if (Schema::hasColumn('rekenings', 'no_rek')) {
                    $table->string('no_rek', 4)->change();
                }
            });
        }

        if (Schema::hasTable('nomor_bantus')) {
            Schema::table('nomor_bantus', function (Blueprint $table) {
                if (Schema::hasColumn('nomor_bantus', 'no_bantu')) {
                    $table->string('no_bantu', 3)->change();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('nomor_bantus')) {
            Schema::table('nomor_bantus', function (Blueprint $table) {
                if (Schema::hasColumn('nomor_bantus', 'no_bantu')) {
                    $table->string('no_bantu', 2)->change();
                }
            });
        }
    }
};
