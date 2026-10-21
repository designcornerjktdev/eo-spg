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

            if ($this->shouldMask($viewer, $submission, $field?->is_identity ?? false)) {
                $masked[$key] = $this->maskValue($value);

                continue;
            }

            $masked[$key] = $value;
        }

        return $masked;
    }

    public function maskValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($item) => $this->maskValue($item), $value);
        }

        $string = (string) $value;
        $length = mb_strlen($string);

        if ($length <= 2) {
            return str_repeat('*', max($length, 1));
        }

        return mb_substr($string, 0, 1).str_repeat('*', $length - 2).mb_substr($string, -1);
    }

    private function shouldMask(User $viewer, FormSubmission $submission, bool $isIdentity): bool
    {
        return $viewer->isClient()
            && $isIdentity
            && $submission->campaign?->mask_identity_for_client;
    }
}
