<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_harian_keuangans', function (Blueprint $table) {
            $table->id();

            // Basic Info
            $table->date('tanggal')->comment('Tanggal laporan harian');
            $table->string('no_reff')->default('LHK')->comment('Nomor referensi: LHK');
            $table->string('nomor_bukti', 50)->nullable()->comment('Nomor bukti/dokumen');
            $table->text('keterangan')->nullable()->comment('Keterangan laporan');

            // Type: Pemasukan (Kas/Bank masuk) atau Pengeluaran (Kas/Bank keluar)
            $table->enum('jenis', ['pemasukan', 'pengeluaran'])->comment('Jenis transaksi: pemasukan/pengeluaran');

            // Kas/Bank Account (Debit untuk Pemasukan, Kredit untuk Pengeluaran)
            $table->unsignedBigInteger('kas_bank_id')->comment('Nomor bantu Kas/Bank');
            $table->unsignedBigInteger('kelompok_id')->comment('Kelompok rekening');
            $table->unsignedBigInteger('rekening_id')->comment('Rekening Kas/Bank');

            // Project Code
            $table->unsignedBigInteger('kode_proyek_id')->nullable()->comment('Kode proyek (opsional)');

            // Company Reference
            $table->unsignedBigInteger('company_id')->default(1)->comment('ID Perusahaan');

            // Status
            $table->boolean('is_confirmed')->default(false)->comment('Status konfirmasi');
            $table->unsignedBigInteger('confirmed_by')->nullable()->comment('User yang konfirmasi');
            $table->timestamp('confirmed_at')->nullable()->comment('Tanggal konfirmasi');

            $table->timestamps();
            $table->softDeletes();

            // Created/Deleted by
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            // Foreign Key Constraints
            $table->foreign('kelompok_id')->references('id')->on('kelompoks')->onDelete('restrict');
            $table->foreign('rekening_id')->references('id')->on('rekenings')->onDelete('restrict');
            $table->foreign('kas_bank_id')->references('id')->on('nomor_bantus')->onDelete('restrict');
            $table->foreign('kode_proyek_id')->references('id')->on('kode_proyeks')->onDelete('set null');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('confirmed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index(['tanggal', 'company_id']);
            $table->index(['jenis']);
            $table->index(['is_confirmed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_harian_keuangans');
    }
};