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
        Schema::create('dokumentasis', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->string('kategori')->nullable()->comment('Kategori: Panduan, Tutorial, FAQ, dll');
            $table->text('deskripsi')->nullable();
            $table->longText('konten')->nullable()->comment('Konten dokumentasi (Rich Text/HTML)');
            $table->string('file_attachment')->nullable()->comment('Path file PDF/Word/Excel');
            $table->integer('urutan')->default(0)->comment('Urutan tampilan');
            $table->boolean('is_published')->default(false);
            $table->boolean('is_manual_book')->default(false)->comment('True = Manual Book, False = Dokumentasi');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['kategori', 'is_published']);
            $table->index(['is_manual_book', 'urutan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dokumentasis');
    }
};
