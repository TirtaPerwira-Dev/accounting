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
        Schema::create('jurnal_rekening_air', function (Blueprint $table) {
            $table->id();

            // Basic Info
            $table->string('no_reff')->unique()->comment('Nomor referensi: 2-1/tahun');
            $table->date('tanggal')->comment('Tanggal transaksi');
            $table->string('bukti')->nullable()->comment('Nomor bukti/dokumen');
            $table->decimal('rp', 15, 2)->comment('Jumlah rupiah total');
            $table->text('keterangan')->nullable()->comment('Keterangan transaksi');

            // Akun Kredit (Pendapatan Air & Non Air)
            $table->unsignedBigInteger('kelompok_kredit_id')->comment('Kelompok akun kredit');
            $table->unsignedBigInteger('rekening_kredit_id')->comment('Rekening akun kredit');
            $table->unsignedBigInteger('nomor_bantu_kredit_id')->comment('Nomor bantu akun kredit');
            $table->char('data_k', 1)->nullable()->comment('Debit/Kredit dari tabel rekening (untuk validasi)');
            $table->string('nama_nomor_bantu_kredit')->nullable()->comment('Nama nomor bantu kredit (manual input)');

            // JSON column for rekening air items (repeater data)
            $table->json('rekening_air_items')->nullable()->comment('Data items rekening air dalam format JSON');

            // Project Code
            $table->unsignedBigInteger('kode_proyek_id')->nullable()->comment('Kode proyek (opsional)');

            // Company Reference
            $table->unsignedBigInteger('company_id')->default(1)->comment('ID Perusahaan');

            // Confirmation Status
            $table->boolean('is_confirmed')->default(false)->comment('Status konfirmasi jurnal');
            $table->unsignedBigInteger('confirmed_by')->nullable()->comment('User yang mengkonfirmasi');
            $table->timestamp('confirmed_at')->nullable()->comment('Waktu konfirmasi');

            $table->timestamps();

            // Foreign Keys
            $table->foreign('kelompok_kredit_id')->references('id')->on('kelompoks')->onDelete('restrict');
            $table->foreign('rekening_kredit_id')->references('id')->on('rekenings')->onDelete('restrict');
            $table->foreign('nomor_bantu_kredit_id')->references('id')->on('nomor_bantus')->onDelete('restrict');
            $table->foreign('kode_proyek_id')->references('id')->on('kode_proyeks')->onDelete('set null');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('confirmed_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('tanggal');
            $table->index('company_id');
            $table->index(
                ['kelompok_kredit_id', 'rekening_kredit_id', 'nomor_bantu_kredit_id'],
                'jra_k_r_nb_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jurnal_rekening_air');
    }
};
