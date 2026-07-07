<?php

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'subtitle' => $this->faker->sentence(5),
            'published_year' => $this->faker->year(),
            'isbn' => $this->faker->isbn13(),
            'pages' => $this->faker->numberBetween(100, 1000),
            'edition' => $this->faker->numberBetween(1, 10),
            'publisher' => $this->faker->company(),
            'language' => $this->faker->languageCode(),
            'description' => $this->faker->paragraph(),
        ];
    }
}
