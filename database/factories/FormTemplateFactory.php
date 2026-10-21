<?php

namespace Database\Factories;

use App\Enums\FormStatus;
use App\Models\Campaign;
use App\Models\FormTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormTemplate>
 */
class FormTemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'name' => 'Form Sampling',
            'version' => 1,
            'status' => FormStatus::Draft,
            'requires_consent' => false,
            'consent_text' => null,
            'qualification_enabled' => false,
            'qualification_rule' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => FormStatus::Published,
        ]);
    }
}
