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
        Schema::table('jurnal_pembelians', function (Blueprint $table) {
            // Kembalikan kolom JSON pembelian_items (yang sudah ada)
            if (!Schema::hasColumn('jurnal_pembelians', 'pembelian_items')) {
                $table->json('pembelian_items')->nullable()->comment('Data items pembelian dalam format JSON');
            }

            // Tambah kolom untuk detail item individual
            $table->string('bukti_item')->nullable()->comment('Bukti transaksi untuk item ini');
            $table->text('keterangan_item')->nullable()->comment('Keterangan untuk item ini');
            $table->decimal('jumlah_item', 15, 2)->nullable()->comment('Jumlah item ini dalam rupiah');

            // Tambah kolom untuk akun debit (item pembelian)
            $table->unsignedBigInteger('kelompok_debit_id')->nullable()->comment('Kelompok akun debit untuk item ini');
            $table->unsignedBigInteger('rekening_debit_id')->nullable()->comment('Rekening akun debit untuk item ini');
            $table->unsignedBigInteger('nomor_bantu_debit_id')->nullable()->comment('Nomor bantu akun debit untuk item ini');

            // Tambah field untuk identify group transaksi
            $table->string('group_transaksi')->nullable()->comment('ID group untuk multiple rows dari input yang sama');
            $table->integer('item_sequence')->nullable()->comment('Urutan item dalam group (1,2,3...)');

            // Foreign keys untuk akun debit
            $table->foreign('kelompok_debit_id')->references('id')->on('kelompoks')->onDelete('restrict');
            $table->foreign('rekening_debit_id')->references('id')->on('rekenings')->onDelete('restrict');
            $table->foreign('nomor_bantu_debit_id')->references('id')->on('nomor_bantus')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jurnal_pembelians', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['kelompok_debit_id']);
            $table->dropForeign(['rekening_debit_id']);
            $table->dropForeign(['nomor_bantu_debit_id']);

            // Drop added columns
            $table->dropColumn([
                'bukti_item',
                'keterangan_item',
                'jumlah_item',
                'kelompok_debit_id',
                'rekening_debit_id',
                'nomor_bantu_debit_id',
                'group_transaksi',
                'item_sequence'
            ]);
        });
    }
};
