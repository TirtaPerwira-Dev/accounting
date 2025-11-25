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
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            // Add SAKEP structure fields
            $table->string('no_kel', 2)->nullable()->after('company_id')->comment('Nomor Kelompok');
            $table->string('no_rek', 4)->nullable()->after('no_kel')->comment('Nomor Rekening');
            $table->string('no_bantu', 2)->nullable()->after('no_rek')->comment('Nomor Bantu');
            $table->string('nama_kel')->nullable()->after('no_bantu')->comment('Nama Kelompok');
            $table->string('kel', 1)->nullable()->after('nama_kel')->comment('KEL (1-6)');
            $table->string('kode', 1)->nullable()->after('kel')->comment('Kode D/K');

            // Add indexes for performance
            $table->index(['company_id', 'no_kel']);
            $table->index(['company_id', 'no_rek']);
            $table->index(['company_id', 'no_bantu']);
            $table->index(['company_id', 'no_kel', 'no_rek', 'no_bantu']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'no_kel']);
            $table->dropIndex(['company_id', 'no_rek']);
            $table->dropIndex(['company_id', 'no_bantu']);
            $table->dropIndex(['company_id', 'no_kel', 'no_rek', 'no_bantu']);

            $table->dropColumn(['no_kel', 'no_rek', 'no_bantu', 'nama_kel', 'kel', 'kode']);
        });
    }
};
