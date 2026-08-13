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
            if (!Schema::hasColumn('jurnal_rekening_air', 'total_item_input_debit')) {
                $table->unsignedInteger('total_item_input_debit')->nullable()->after('total_item_input');
            }

            if (!Schema::hasColumn('jurnal_rekening_air', 'total_item_input_kredit')) {
                $table->unsignedInteger('total_item_input_kredit')->nullable()->after('total_item_input_debit');
            }

            if (!Schema::hasColumn('jurnal_rekening_air', 'nominal_input_debit')) {
                $table->decimal('nominal_input_debit', 15, 2)->nullable()->after('nominal_input');
            }

            if (!Schema::hasColumn('jurnal_rekening_air', 'nominal_input_kredit')) {
                $table->decimal('nominal_input_kredit', 15, 2)->nullable()->after('nominal_input_debit');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jurnal_rekening_air', function (Blueprint $table) {
            $dropColumns = [];

            if (Schema::hasColumn('jurnal_rekening_air', 'nominal_input_kredit')) {
                $dropColumns[] = 'nominal_input_kredit';
            }
            if (Schema::hasColumn('jurnal_rekening_air', 'nominal_input_debit')) {
                $dropColumns[] = 'nominal_input_debit';
            }
            if (Schema::hasColumn('jurnal_rekening_air', 'total_item_input_kredit')) {
                $dropColumns[] = 'total_item_input_kredit';
            }
            if (Schema::hasColumn('jurnal_rekening_air', 'total_item_input_debit')) {
                $dropColumns[] = 'total_item_input_debit';
            }

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
