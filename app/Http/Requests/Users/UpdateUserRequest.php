<?php

namespace App\Http\Requests\Users;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');

        return ($this->user()?->isSuperadmin() ?? false) && ! $target->isSuperadmin();
    }

    /**
     * The role cannot change after creation; only client accounts are managed here.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $target = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9._-]+$/', Rule::unique('users', 'username')->ignore($target->id)],
            'is_active' => ['required', 'boolean'],
            'client_id' => [Rule::requiredIf($target->role === UserRole::Client), 'nullable', 'exists:clients,id'],
        ];
    }
}
