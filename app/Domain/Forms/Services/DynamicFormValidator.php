<?php

namespace App\Domain\Forms\Services;

use App\Enums\FieldType;
use App\Models\FormTemplate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DynamicFormValidator
{
    /**
     * @param  array<string, mixed>  $answers
     * @return array<string, mixed>
     */
    public function validate(FormTemplate $template, array $answers, bool $consentAccepted = false): array
    {
        $rules = [];
        $attributes = [];

        foreach ($template->fields as $field) {
            $key = 'answers.'.$field->key;
            $attributes[$key] = $field->label;
            $fieldRules = $field->required ? ['required'] : ['nullable'];

            $fieldRules = array_merge($fieldRules, match ($field->type) {
                FieldType::ShortText => ['string', 'max:255'],
                FieldType::LongText => ['string', 'max:2000'],
                FieldType::Number => array_values(array_filter([
                    'numeric',
                    $field->min_value !== null ? 'min:'.$field->min_value : null,
                    $field->max_value !== null ? 'max:'.$field->max_value : null,
                ])),
                FieldType::Phone => ['string', 'regex:/^[0-9+\\-\\s]{8,20}$/'],
                FieldType::SingleChoice => ['string', 'in:'.implode(',', $this->optionValues($field->options))],
                FieldType::MultipleChoice => ['array'],
                FieldType::YesNo => ['boolean'],
                FieldType::Date => ['date'],
            });

            $rules[$key] = $fieldRules;

            if ($field->type === FieldType::MultipleChoice) {
                $rules[$key.'.*'] = ['string', 'in:'.implode(',', $this->optionValues($field->options))];
            }
        }

        if ($template->requires_consent) {
            $rules['consent_accepted'] = ['accepted'];
            $attributes['consent_accepted'] = 'persetujuan';
        }

        $validator = Validator::make(
            [
                'answers' => $answers,
                'consent_accepted' => $consentAccepted,
            ],
            $rules,
            [
                'accepted' => 'Consent must be checked before saving.',
            ],
            $attributes,
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated()['answers'] ?? [];
    }

    /**
     * @param  array<int, string|array{value?: string, label?: string}>|null  $options
     * @return array<int, string>
     */
    private function optionValues(?array $options): array
    {
        return collect($options ?? [])
            ->map(fn ($option) => is_array($option) ? ($option['value'] ?? $option['label'] ?? '') : (string) $option)
            ->filter()
            ->values()
            ->all();
    }
}
