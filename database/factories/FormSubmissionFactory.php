<?php

namespace Database\Factories;

use App\Enums\SubmissionStatus;
use App\Models\Campaign;
use App\Models\CampaignLocation;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormSubmission>
 */
class FormSubmissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'location_id' => CampaignLocation::factory(),
            'form_template_id' => FormTemplate::factory(),
            'answers' => ['nama' => 'Andi'],
            'consent_accepted' => false,
            'consent_text_version' => null,
            'qualified' => null,
            'submitted_at' => now(),
            'status' => SubmissionStatus::Submitted,
        ];
    }
}
