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
        Schema::create('jurnal_pemakaian_bahans', function (Blueprint $table) {
            $table->id();
            $table->string('no_reff')->unique();
            $table->date('tanggal')->comment('Tanggal transaksi');
            $table->string('bukti')->nullable();
            $table->string('beban_bagian')->nullable()->comment('Beban bagian');
            $table->string('dibayar')->nullable();
            $table->string('no_check')->nullable();

            $table->unsignedBigInteger('kelompok_debit_id');
            $table->unsignedBigInteger('rekening_debit_id');
            $table->unsignedBigInteger('nomor_bantu_debit_id')->nullable();
            $table->string('data_debit', 10)->nullable();

            $table->unsignedBigInteger('kelompok_kredit_id');
            $table->unsignedBigInteger('rekening_kredit_id');
            $table->unsignedBigInteger('nomor_bantu_kredit_id')->nullable();
            $table->string('data_kredit', 10)->nullable();

            $table->decimal('rp', 15, 2);
            $table->text('keterangan')->nullable();
            $table->text('keterangan_1')->nullable();
            $table->text('keterangan_2')->nullable();
            $table->text('keterangan_3')->nullable();
            $table->text('keterangan_4')->nullable();
            $table->char('ref', 1)->default('4')->comment('Ref absolut = 4');
            $table->unsignedBigInteger('kode_proyek_id')->nullable();

            $table->string('group_transaksi')->nullable();
            $table->integer('item_sequence')->default(0);
            $table->unsignedBigInteger('company_id')->default(1);
            $table->boolean('is_confirmed')->default(false);
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->foreign('kelompok_debit_id')->references('id')->on('kelompoks')->onDelete('restrict');
            $table->foreign('rekening_debit_id')->references('id')->on('rekenings')->onDelete('restrict');
            $table->foreign('nomor_bantu_debit_id')->references('id')->on('nomor_bantus')->onDelete('restrict');
            $table->foreign('kelompok_kredit_id')->references('id')->on('kelompoks')->onDelete('restrict');
            $table->foreign('rekening_kredit_id')->references('id')->on('rekenings')->onDelete('restrict');
            $table->foreign('nomor_bantu_kredit_id')->references('id')->on('nomor_bantus')->onDelete('restrict');
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
        Schema::dropIfExists('jurnal_pemakaian_bahans');
    }
};
