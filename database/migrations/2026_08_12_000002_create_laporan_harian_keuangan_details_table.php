<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('laporan_harian_keuangan_details')) {
            Schema::create('laporan_harian_keuangan_details', function (Blueprint $table) {
                $table->id();
                $table->foreignId('laporan_harian_keuangan_id');
                $table->foreignId('kelompok_id')->nullable();
                $table->foreignId('rekening_id')->nullable();
                $table->foreignId('nomor_bantu_id')->nullable();
                $table->foreignId('kode_proyek_id')->nullable();
                $table->string('nomor_bukti')->nullable();
                $table->decimal('jumlah', 15, 2)->default(0);
                $table->text('keterangan_item')->nullable();
                $table->timestamps();
            });
        }

        if (!$this->foreignKeyExists('laporan_harian_keuangan_details', 'fk_lhkd_lhk')) {
            Schema::table('laporan_harian_keuangan_details', function (Blueprint $table) {
                $table->foreign('laporan_harian_keuangan_id', 'fk_lhkd_lhk')
                    ->references('id')
                    ->on('laporan_harian_keuangans')
                    ->onDelete('cascade');
            });
        }

        if (!$this->foreignKeyExists('laporan_harian_keuangan_details', 'fk_lhkd_kel')) {
            Schema::table('laporan_harian_keuangan_details', function (Blueprint $table) {
                $table->foreign('kelompok_id', 'fk_lhkd_kel')
                    ->references('id')
                    ->on('kelompoks');
            });
        }

        if (!$this->foreignKeyExists('laporan_harian_keuangan_details', 'fk_lhkd_rek')) {
            Schema::table('laporan_harian_keuangan_details', function (Blueprint $table) {
                $table->foreign('rekening_id', 'fk_lhkd_rek')
                    ->references('id')
                    ->on('rekenings');
            });
        }

        if (!$this->foreignKeyExists('laporan_harian_keuangan_details', 'fk_lhkd_nb')) {
            Schema::table('laporan_harian_keuangan_details', function (Blueprint $table) {
                $table->foreign('nomor_bantu_id', 'fk_lhkd_nb')
                    ->references('id')
                    ->on('nomor_bantus');
            });
        }

        if (!$this->foreignKeyExists('laporan_harian_keuangan_details', 'fk_lhkd_kp')) {
            Schema::table('laporan_harian_keuangan_details', function (Blueprint $table) {
                $table->foreign('kode_proyek_id', 'fk_lhkd_kp')
                    ->references('id')
                    ->on('kode_proyeks');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_harian_keuangan_details');
    }

    private function foreignKeyExists(string $tableName, string $constraintName): bool
    {
        $databaseName = DB::getDatabaseName();

        $result = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $databaseName)
            ->where('TABLE_NAME', $tableName)
            ->where('CONSTRAINT_NAME', $constraintName)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();

        return (bool) $result;
    }
};
