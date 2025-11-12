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
        // 1. Drop foreign key constraints first (if they exist)
        if (Schema::hasTable('journal_details')) {
            Schema::table('journal_details', function (Blueprint $table) {
                if (Schema::hasColumn('journal_details', 'account_id')) {
                    $table->dropForeign(['account_id']);
                    $table->dropColumn('account_id');
                }
            });
        }

        if (Schema::hasTable('opening_balances')) {
            Schema::table('opening_balances', function (Blueprint $table) {
                if (Schema::hasColumn('opening_balances', 'account_id')) {
                    $table->dropForeign(['account_id']);
                    $table->dropColumn('account_id');
                }
            });
        }

        // 2. Drop chart_of_accounts table
        Schema::dropIfExists('chart_of_accounts');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: This is a destructive migration.
        // Reverting this would require recreating the chart_of_accounts structure
        // and re-mapping data from SAKEP to ChartOfAccount, which is not practical.

        throw new Exception('Cannot reverse this migration. This is a destructive change that removes the chart_of_accounts system in favor of direct SAKEP usage.');
    }
};
