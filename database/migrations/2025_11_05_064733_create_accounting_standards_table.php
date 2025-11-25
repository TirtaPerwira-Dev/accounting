<?php
// database/migrations/2025_11_05_000002_create_accounting_standards_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_standards', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique(); // SAKEP, PSAK, SAP, IFRS
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed data standar
        DB::table('accounting_standards')->insert([
            ['code' => 'SAKEP', 'name' => 'SAKEP - Entitas Privat', 'description' => 'Untuk PDAM, UMKM, non-publik', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'PSAK',  'name' => 'PSAK - Perusahaan Publik', 'description' => 'Untuk perusahaan Tbk', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'SAP',   'name' => 'SAP - Pemerintahan', 'description' => 'Standar Akuntansi Pemerintahan', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'IFRS',  'name' => 'IFRS - Internasional', 'description' => 'International Financial Reporting Standards', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_standards');
    }
};
