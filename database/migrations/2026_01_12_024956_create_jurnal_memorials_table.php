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
        Schema::create('jurnal_memorials', function (Blueprint $table) {
            $table->id();
            $table->string('no_reff')->unique();
            $table->date('tanggal')->comment('Tanggal transaksi');
            $table->string('bukti')->nullable();
            $table->unsignedBigInteger('kelompok_id');
            $table->unsignedBigInteger('rekening_id');
            $table->unsignedBigInteger('nomor_bantu_id')->nullable();
            $table->decimal('rp', 15, 2);
            $table->enum('kode', ['D', 'K'])->comment('Debit/Kredit');
            $table->text('keterangan')->nullable();
            $table->char('ref', 1)->default('6')->comment('Ref absolut = 6');
            $table->unsignedBigInteger('kode_proyek_id')->nullable();
            $table->string('data', 10)->nullable();

            $table->string('group_transaksi')->nullable();
            $table->integer('item_sequence')->default(0);
            $table->unsignedBigInteger('company_id')->default(1);
            $table->boolean('is_confirmed')->default(false);
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->foreign('kelompok_id')->references('id')->on('kelompoks')->onDelete('restrict');
            $table->foreign('rekening_id')->references('id')->on('rekenings')->onDelete('restrict');
            $table->foreign('nomor_bantu_id')->references('id')->on('nomor_bantus')->onDelete('restrict');
            $table->foreign('kode_proyek_id')->references('id')->on('kode_proyeks')->onDelete('set null');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('confirmed_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['tanggal', 'company_id']);
            $table->index(['group_transaksi']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jurnal_memorials');
    }
};
