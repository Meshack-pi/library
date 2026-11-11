<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Loan>
 */
class LoanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'book_id' => Book::factory(),
            'issued_by' => User::factory(),
            'issued_at' => now(),
            'due_at' => today()->addDays(14),
            'returned_at' => null,
            'renewals_count' => 0,
        ];
    }

    /**
     * A loan that is still out and was due the given number of days ago.
     */
    public function overdueBy(int $days): static
    {
        return $this->state(fn (): array => [
            'issued_at' => now()->subDays($days + 14),
            'due_at' => today()->subDays($days),
        ]);
    }

    public function returned(): static
    {
        return $this->state(fn (): array => ['returned_at' => now()]);
    }
}
