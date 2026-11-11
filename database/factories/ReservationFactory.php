<?php

namespace Database\Factories;

use App\Enums\ReservationStatus;
use App\Models\Book;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'book_id' => Book::factory(),
            'status' => ReservationStatus::Pending,
        ];
    }

    /**
     * A reservation with a copy held on the shelf for the member.
     */
    public function ready(): static
    {
        return $this->state(fn (): array => [
            'status' => ReservationStatus::Ready,
            'ready_at' => now(),
            'expires_at' => today()->addDays(3)->endOfDay(),
        ]);
    }
}
