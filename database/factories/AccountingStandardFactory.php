<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AccountingStandard>
 */
class AccountingStandardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $standards = [
            ['code' => 'SAKEP', 'name' => 'SAKEP - Entitas Privat', 'description' => 'Untuk PDAM, UMKM, non-publik'],
            ['code' => 'PSAK', 'name' => 'PSAK - Perusahaan Publik', 'description' => 'Untuk perusahaan Tbk'],
            ['code' => 'SAP', 'name' => 'SAP - Pemerintahan', 'description' => 'Standar Akuntansi Pemerintahan'],
            ['code' => 'IFRS', 'name' => 'IFRS - Internasional', 'description' => 'International Financial Reporting Standards'],
            ['code' => 'ETAP', 'name' => 'SAK ETAP', 'description' => 'Entitas Tanpa Akuntabilitas Publik'],
            ['code' => 'EMKM', 'name' => 'SAK EMKM', 'description' => 'Entitas Mikro Kecil Menengah'],
        ];

        $standard = $this->faker->randomElement($standards);

        return [
            'code' => $standard['code'],
            'name' => $standard['name'],
            'description' => $standard['description'],
            'is_active' => $this->faker->boolean(85), // 85% active
        ];
    }
}
