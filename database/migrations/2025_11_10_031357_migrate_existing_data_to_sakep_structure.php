<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Memindahkan data existing ke struktur SAKEP baru
     */
    public function up(): void
    {
        // Update chart_of_accounts jika sudah ada yang menggunakan SAKEP (PostgreSQL syntax)
        DB::statement("
            UPDATE chart_of_accounts
            SET
                kelompok_id = CASE
                    WHEN nomor_bantu_id IS NOT NULL THEN (
                        SELECT r.kelompok_id FROM nomor_bantus nb
                        JOIN rekenings r ON r.id = nb.rekening_id
                        WHERE nb.id = chart_of_accounts.nomor_bantu_id
                    )
                    WHEN rekening_id IS NOT NULL THEN (
                        SELECT kelompok_id FROM rekenings WHERE id = chart_of_accounts.rekening_id
                    )
                    ELSE kelompok_id
                END,
                rekening_id = CASE
                    WHEN nomor_bantu_id IS NOT NULL THEN (
                        SELECT rekening_id FROM nomor_bantus WHERE id = chart_of_accounts.nomor_bantu_id
                    )
                    ELSE rekening_id
                END
            WHERE nomor_bantu_id IS NOT NULL OR rekening_id IS NOT NULL OR kelompok_id IS NOT NULL
        ");

        // Update journal_details untuk menggunakan SAKEP references
        DB::statement("
            UPDATE journal_details
            SET
                kelompok_id = (SELECT kelompok_id FROM chart_of_accounts WHERE id = journal_details.account_id),
                rekening_id = (SELECT rekening_id FROM chart_of_accounts WHERE id = journal_details.account_id),
                nomor_bantu_id = (SELECT nomor_bantu_id FROM chart_of_accounts WHERE id = journal_details.account_id)
            WHERE account_id IS NOT NULL
            AND EXISTS (SELECT 1 FROM chart_of_accounts WHERE id = journal_details.account_id AND kelompok_id IS NOT NULL)
        ");

        // Update opening_balances untuk menggunakan SAKEP references
        DB::statement("
            UPDATE opening_balances
            SET
                kelompok_id = (SELECT kelompok_id FROM chart_of_accounts WHERE id = opening_balances.account_id),
                rekening_id = (SELECT rekening_id FROM chart_of_accounts WHERE id = opening_balances.account_id),
                nomor_bantu_id = (SELECT nomor_bantu_id FROM chart_of_accounts WHERE id = opening_balances.account_id)
            WHERE account_id IS NOT NULL
            AND EXISTS (SELECT 1 FROM chart_of_accounts WHERE id = opening_balances.account_id AND kelompok_id IS NOT NULL)
        ");

        echo "Data migration to SAKEP structure completed!" . PHP_EOL;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Clear SAKEP references from journal_details
        DB::statement("
            UPDATE journal_details
            SET kelompok_id = NULL, rekening_id = NULL, nomor_bantu_id = NULL
        ");

        // Clear SAKEP references from opening_balances
        DB::statement("
            UPDATE opening_balances
            SET kelompok_id = NULL, rekening_id = NULL, nomor_bantu_id = NULL
        ");

        echo "SAKEP data migration rollback completed!" . PHP_EOL;
    }
};
