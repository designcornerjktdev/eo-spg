<?php

namespace App\Http\Requests\Forms;

use App\Enums\FieldType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FormTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStaff() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'campaign_id' => ['required', 'exists:campaigns,id'],
            'name' => ['required', 'string', 'max:255'],
            'requires_consent' => ['sometimes', 'boolean'],
            'consent_text' => ['nullable', 'required_if:requires_consent,true', 'string'],
            'qualification_enabled' => ['sometimes', 'boolean'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.key' => ['required', 'string', 'max:80'],
            'fields.*.label' => ['required', 'string', 'max:255'],
            'fields.*.type' => ['required', Rule::enum(FieldType::class)],
            'fields.*.required' => ['sometimes', 'boolean'],
            'fields.*.sort_order' => ['nullable', 'integer'],
            'fields.*.visible_to_client' => ['sometimes', 'boolean'],
            'fields.*.show_on_dashboard' => ['sometimes', 'boolean'],
            'fields.*.options' => ['nullable', 'array'],
            'fields.*.min_value' => ['nullable', 'numeric'],
            'fields.*.max_value' => ['nullable', 'numeric'],
        ];
    }
}
