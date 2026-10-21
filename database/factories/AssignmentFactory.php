<?php

namespace Database\Factories;

use App\Enums\AssignmentStatus;
use App\Models\Assignment;
use App\Models\Campaign;
use App\Models\CampaignLocation;
use App\Models\FormTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assignment>
 */
class AssignmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'location_id' => CampaignLocation::factory(),
            'form_template_id' => FormTemplate::factory(),
            'user_id' => User::factory()->spg(),
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHours(6),
            'status' => AssignmentStatus::Active,
        ];
    }
}
