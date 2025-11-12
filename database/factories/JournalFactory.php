<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Journal>
 */
class JournalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $descriptions = [
            'Pembayaran tagihan air RT bulan ' . $this->faker->monthName,
            'Penjualan air niaga ' . $this->faker->company,
            'Pembayaran gaji karyawan bulan ' . $this->faker->monthName,
            'Pembelian bahan kimia PAC',
            'Pembayaran listrik pompa air',
            'Penerimaan pembayaran pelanggan',
            'Pembelian suku cadang pompa',
            'Biaya pemeliharaan pipa distribusi',
            'Pendapatan pasang sambungan baru',
            'Pembayaran utang supplier',
            'Penerimaan tagihan air industri',
            'Biaya operasional kantor',
            'Pembayaran asuransi kendaraan',
            'Penjualan air curah',
            'Biaya transportasi operasional'
        ];

        return [
            'company_id' => Company::factory(),
            'transaction_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'reference' => 'JU-' . date('Ym') . '-' . $this->faker->unique()->numberBetween(1000, 9999),
            'description' => $this->faker->randomElement($descriptions),
            'status' => $this->faker->randomElement(['draft', 'posted']),
            'total_amount' => $this->faker->randomFloat(2, 100000, 10000000),
        ];
    }
}
