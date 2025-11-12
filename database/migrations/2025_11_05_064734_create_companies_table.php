<?php
// database/migrations/2025_11_05_000001_create_companies_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Perumdam Tirta Perwira
            $table->string('npwp', 20)->unique();
            $table->string('address');
            $table->string('phone')->nullable();
            $table->string('logo')->nullable();
            $table->foreignId('accounting_standard_id')->nullable()->constrained('accounting_standards');
            $table->json('config')->nullable(); // { "ppn_rate": 11, "efaktur_seri": "010.001-25." }
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
