<?php

namespace App\Domain\Reporting\Services;

use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\User;

class IdentityMasker
{
    /**
     * @param  array<string, mixed>  $answers
     * @return array<string, mixed>
     */
    public function maskAnswers(User $viewer, FormSubmission $submission, array $answers, FormTemplate $template): array
    {
        $fields = $template->fields->keyBy('key');
        $masked = [];

        foreach ($answers as $key => $value) {
            $field = $fields->get($key);

            if ($viewer->isClient() && $field && ! $field->visible_to_client) {
                continue;
            }

            $masked[$key] = $value;
        }

        return $masked;
    }
}
