<?php

namespace App\Http\Requests;

use App\PlainText;
use App\Rules\FilterEmail;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() === null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'organization_name' => PlainText::clean($this->input('organization_name')),
            'name' => PlainText::clean($this->input('name')),
            'email' => PlainText::clean($this->input('email')),
            'password' => PlainText::withoutNullBytes($this->input('password')),
            'password_confirmation' => PlainText::withoutNullBytes($this->input('password_confirmation')),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'organization_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'max:255', new FilterEmail, Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
        ];
    }
}
