<?php

namespace Database\Factories;

use App\Enums\SubmissionStatus;
use App\Models\Assignment;
use App\Models\Campaign;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\User;
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
            'assignment_id' => Assignment::factory(),
            'form_template_id' => FormTemplate::factory(),
            'submitted_by' => User::factory()->spg(),
            'answers' => ['nama' => 'Andi'],
            'consent_accepted' => false,
            'consent_text_version' => null,
            'qualified' => null,
            'submitted_at' => now(),
            'status' => SubmissionStatus::Submitted,
        ];
    }
}
