<?php

namespace App\Http\Requests\Campaigns;

use App\Models\Product;
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
            'client_id' => ['required', 'exists:clients,id'],
            'name' => ['required', 'string', 'max:255'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'notes' => ['nullable', 'string'],
            'mask_identity_for_client' => ['sometimes', 'boolean'],
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['exists:products,id'],
            'locations' => ['required', 'array', 'min:1'],
            'locations.*.name' => ['required', 'string', 'max:255'],
            'locations.*.area' => ['nullable', 'string', 'max:255'],
            'locations.*.address' => ['nullable', 'string', 'max:255'],
            'locations.*.latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:locations.*.longitude'],
            'locations.*.longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:locations.*.latitude'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $productIds = $this->input('product_ids', []);
            $owned = Product::query()
                ->where('client_id', $this->input('client_id'))
                ->whereIn('id', $productIds)
                ->count();

            if ($owned !== count($productIds)) {
                $validator->errors()->add('product_ids', 'All products must belong to the campaign client.');
            }
        });
    }
}
