<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'code' => Str::upper(Str::slug(Str::limit($name, 12, ''))).'-'.fake()->unique()->numerify('###'),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
