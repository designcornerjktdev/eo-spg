<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\CampaignLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampaignLocation>
 */
class CampaignLocationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'name' => fake()->streetName(),
            'area' => fake()->city(),
            'address' => fake()->address(),
        ];
    }
}
