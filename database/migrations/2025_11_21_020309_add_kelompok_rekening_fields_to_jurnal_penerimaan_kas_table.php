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
            // Add kelompok_id and rekening_id fields for cascade selection
            $table->unsignedBigInteger('kelompok_id')->nullable()->after('id');
            $table->unsignedBigInteger('rekening_id')->nullable()->after('kelompok_id');
            
            // Add foreign key constraints
            $table->foreign('kelompok_id')->references('id')->on('kelompoks')->onDelete('set null');
            $table->foreign('rekening_id')->references('id')->on('rekenings')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jurnal_penerimaan_kas', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['kelompok_id']);
            $table->dropForeign(['rekening_id']);
            
            // Drop columns
            $table->dropColumn(['kelompok_id', 'rekening_id']);
        });
    }
};
