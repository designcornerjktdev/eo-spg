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
            'account_name' => ['nullable', 'required_with:account_email', 'string', 'max:255'],
            'account_email' => ['nullable', 'required_with:account_name', 'email', 'unique:users,email'],
            'account_password' => ['nullable', 'required_with:account_email', Password::defaults()],
        ];
    }
}
