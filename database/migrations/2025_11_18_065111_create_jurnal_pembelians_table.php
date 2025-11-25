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
        Schema::create('jurnal_pembelians', function (Blueprint $table) {
            $table->id();

            // Basic Info
            $table->string('no_reff')->unique()->comment('Nomor referensi: 1-1/tahun');
            $table->date('tanggal')->comment('Tanggal transaksi');
            $table->string('bukti')->nullable()->comment('Nomor bukti/dokumen');
            $table->decimal('rp', 15, 2)->comment('Jumlah rupiah');
            $table->text('keterangan')->nullable()->comment('Keterangan transaksi');

            // Akun Debit (Yang dibeli/asset/beban)
            $table->unsignedBigInteger('kelompok_debit_id')->comment('Kelompok akun debit');
            $table->unsignedBigInteger('rekening_debit_id')->comment('Rekening akun debit');
            $table->unsignedBigInteger('nomor_bantu_debit_id')->comment('Nomor bantu akun debit');
            $table->char('data_d', 1)->comment('Debit/Kredit dari tabel rekening (untuk validasi)');

            // Akun Kredit (Kas/Bank/Hutang)
            $table->unsignedBigInteger('kelompok_kredit_id')->comment('Kelompok akun kredit');
            $table->unsignedBigInteger('rekening_kredit_id')->comment('Rekening akun kredit');
            $table->unsignedBigInteger('nomor_bantu_kredit_id')->comment('Nomor bantu akun kredit');
            $table->char('data_k', 1)->comment('Debit/Kredit dari tabel rekening (untuk validasi)');

            // Project Code
            $table->unsignedBigInteger('kode_proyek_id')->nullable()->comment('Kode proyek (opsional)');

            // Company Reference
            $table->unsignedBigInteger('company_id')->default(1)->comment('ID Perusahaan');

            // Status
            $table->boolean('is_confirmed')->default(false)->comment('Status konfirmasi');
            $table->unsignedBigInteger('confirmed_by')->nullable()->comment('User yang konfirmasi');
            $table->timestamp('confirmed_at')->nullable()->comment('Tanggal konfirmasi');

            $table->timestamps();

            // Foreign Key Constraints
            $table->foreign('kelompok_debit_id')->references('id')->on('kelompoks')->onDelete('restrict');
            $table->foreign('rekening_debit_id')->references('id')->on('rekenings')->onDelete('restrict');
            $table->foreign('nomor_bantu_debit_id')->references('id')->on('nomor_bantus')->onDelete('restrict');

            $table->foreign('kelompok_kredit_id')->references('id')->on('kelompoks')->onDelete('restrict');
            $table->foreign('rekening_kredit_id')->references('id')->on('rekenings')->onDelete('restrict');
            $table->foreign('nomor_bantu_kredit_id')->references('id')->on('nomor_bantus')->onDelete('restrict');

            $table->foreign('kode_proyek_id')->references('id')->on('kode_proyeks')->onDelete('set null');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('confirmed_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index(['tanggal', 'company_id']);
            $table->index(['no_reff']);
            $table->index(['is_confirmed']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jurnal_pembelians');
    }
};
