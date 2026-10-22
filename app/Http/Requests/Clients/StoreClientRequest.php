<?php

namespace App\Http\Requests\Clients;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreClientRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:50', 'unique:clients,code'],
            'notes' => ['nullable', 'string'],
            'account_name' => ['nullable', 'required_with:account_username', 'string', 'max:255'],
            'account_username' => ['nullable', 'required_with:account_name', 'string', 'max:50', 'regex:/^[a-z0-9._-]+$/', 'unique:users,username'],
            'account_password' => ['nullable', 'required_with:account_username', Password::defaults()],
        ];
    }
}
