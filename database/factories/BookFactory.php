<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => ucfirst(fake()->words(3, true)),
            'author' => fake()->name(),
            'isbn' => fake()->unique()->isbn13(),
            'category_id' => Category::factory(),
            'shelf_location' => fake()->bothify('?-##'),
            'total_copies' => 1,
        ];
    }

    public function copies(int $copies): static
    {
        return $this->state(fn (): array => ['total_copies' => $copies]);
    }
}
