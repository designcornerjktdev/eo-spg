<?php

namespace Database\Factories;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->toDateString();

        return [
            'name' => fake()->words(3, true),
            'starts_on' => $start,
            'ends_on' => now()->addDays(7)->toDateString(),
            'status' => CampaignStatus::Draft,
            'notes' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => CampaignStatus::Active,
        ]);
    }
}
