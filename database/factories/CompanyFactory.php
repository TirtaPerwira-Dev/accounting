<?php

namespace Database\Factories;

use App\Models\AccountingStandard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $pdamNames = [
            'PDAM Tirta Jaya',
            'PDAM Tirta Bhagasasi',
            'PDAM Tirta Kencana',
            'PDAM Tirta Dharma',
            'PDAM Tirta Bhumi',
            'PDAM Tirta Raya',
            'PDAM Tirta Makmur',
            'PDAM Tirta Sejahtera',
            'PDAM Tirta Utama',
            'PDAM Tirta Mandiri',
            'PDAM Tirta Maju',
            'PDAM Tirta Karya'
        ];

        $cities = [
            'Bandung',
            'Jakarta',
            'Surabaya',
            'Medan',
            'Makassar',
            'Palembang',
            'Semarang',
            'Bekasi',
            'Depok',
            'Tangerang',
            'Yogyakarta',
            'Malang',
            'Bogor',
            'Batam',
            'Pekanbaru',
            'Bandar Lampung',
            'Padang',
            'Denpasar'
        ];

        $city = $this->faker->randomElement($cities);
        $pdamName = $this->faker->randomElement($pdamNames) . ' ' . $city;

        return [
            'name' => $pdamName,
            'npwp' => $this->generateNPWP(),
            'address' => $this->faker->streetAddress . ', ' . $city,
            'phone' => $this->faker->phoneNumber,
            'logo' => null,
            'accounting_standard_id' => AccountingStandard::where('code', 'SAKEP')->first()?->id ?? 1,
            'config' => [
                'ppn_rate' => 11,
                'currency' => 'IDR',
                'fiscal_year_start' => '01-01',
                'efaktur_seri' => '010.001-' . $this->faker->numberBetween(10, 99) . '.',
            ],
            'is_active' => true,
        ];
    }

    private function generateNPWP(): string
    {
        return sprintf(
            '%02d.%03d.%03d.%01d-%03d.%03d',
            $this->faker->numberBetween(10, 99),
            $this->faker->numberBetween(100, 999),
            $this->faker->numberBetween(100, 999),
            $this->faker->numberBetween(1, 9),
            $this->faker->numberBetween(100, 999),
            $this->faker->numberBetween(100, 999)
        );
    }
}
