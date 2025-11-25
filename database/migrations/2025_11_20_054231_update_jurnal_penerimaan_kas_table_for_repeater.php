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
            // Drop old columns
            $table->dropForeign(['kode_proyek_id']);
            $table->dropForeign(['nomor_rekening_id']);
            $table->dropColumn(['kode_proyek_id', 'nomor_rekening_id', 'jumlah']);

            // Add new columns for repeater structure
            $table->json('detail_items')->nullable()->after('keterangan');
            $table->decimal('total_amount', 15, 2)->default(0)->after('detail_items');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jurnal_penerimaan_kas', function (Blueprint $table) {
            // Remove new columns
            $table->dropColumn(['detail_items', 'total_amount']);

            // Add back old columns
            $table->unsignedBigInteger('kode_proyek_id')->nullable()->after('keterangan');
            $table->unsignedBigInteger('nomor_rekening_id')->after('kode_proyek_id');
            $table->decimal('jumlah', 15, 2)->after('nomor_rekening_id');

            // Add back foreign keys
            $table->foreign('kode_proyek_id')->references('id')->on('kode_proyeks')->onDelete('set null');
            $table->foreign('nomor_rekening_id')->references('id')->on('rekenings')->onDelete('restrict');
        });
    }
};
