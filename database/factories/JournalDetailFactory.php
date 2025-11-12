<?php

namespace Database\Factories;

use App\Models\Journal;
use App\Models\ChartOfAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JournalDetail>
 */
class JournalDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = $this->faker->randomFloat(2, 50000, 5000000);
        $isDebit = $this->faker->boolean();

        return [
            'journal_id' => Journal::factory(),
            'account_id' => ChartOfAccount::factory(),
            'description' => $this->faker->sentence(6),
            'debit' => $isDebit ? $amount : 0,
            'credit' => !$isDebit ? $amount : 0,
        ];
    }
}
