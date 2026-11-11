<?php

namespace Database\Factories;

use App\Models\Fine;
use App\Models\Loan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fine>
 */
class FineFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'loan_id' => Loan::factory()->returned(),
            'user_id' => fn (array $attributes) => Loan::findOrFail($attributes['loan_id'])->user_id,
            'amount' => 50,
            'paid_at' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (): array => ['paid_at' => now()]);
    }
}
