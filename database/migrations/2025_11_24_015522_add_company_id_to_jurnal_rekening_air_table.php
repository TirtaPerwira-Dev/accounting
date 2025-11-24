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
        Schema::table('jurnal_rekening_air', function (Blueprint $table) {
            // Add company_id field with default value and foreign key
            $table->unsignedBigInteger('company_id')->default(1)->after('id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jurnal_rekening_air', function (Blueprint $table) {
            // Drop foreign key first, then the column
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
