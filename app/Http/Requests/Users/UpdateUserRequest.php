<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');

        return ! $target->isSuperadmin()
            && ($this->user()?->can('update', $target) || $this->user()?->can('manageCampaignAccess', $target));
    }

    /**
     * The role cannot change after creation; only client accounts are managed here.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $target = $this->route('user');

        // Admins only change which campaigns a client sees.
        if (! $this->user()->isSuperadmin()) {
            return [
                'campaign_ids' => ['array'],
                'campaign_ids.*' => ['integer', 'exists:campaigns,id'],
            ];
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9._-]+$/', Rule::unique('users', 'username')->ignore($target->id)],
            'is_active' => ['required', 'boolean'],
            'email' => $target->isAdmin()
                ? ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($target->id)]
                : ['prohibited'],
            'campaign_ids' => ['array'],
            'campaign_ids.*' => ['integer', 'exists:campaigns,id'],
        ];
    }
}
