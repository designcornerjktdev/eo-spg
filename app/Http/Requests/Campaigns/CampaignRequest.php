<?php

namespace App\Http\Requests\Campaigns;

use Illuminate\Foundation\Http\FormRequest;

class CampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperadmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'notes' => ['nullable', 'string'],
            'mask_identity_for_client' => ['sometimes', 'boolean'],
            'locations' => ['required', 'array', 'min:1'],
            'locations.*.name' => ['required', 'string', 'max:255'],
            'locations.*.area' => ['nullable', 'string', 'max:255'],
            'locations.*.address' => ['nullable', 'string', 'max:255'],
            'locations.*.latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:locations.*.longitude'],
            'locations.*.longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:locations.*.latitude'],
            'locations.*.radius_meters' => ['nullable', 'integer', 'between:50,5000'],
        ];
    }
}
