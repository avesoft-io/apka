<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesOfficeActionDetails;
use App\Models\OfficeAction;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreOfficeActionRequest extends FormRequest
{
    use ValidatesOfficeActionDetails;

    public function authorize(): bool
    {
        return $this->user()?->can('create', OfficeAction::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareOfficeActionDetails();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->officeActionRules(allowNewClient: true, allowAssignee: true);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->officeActionMessages();
    }

    public function withValidator(Validator $validator): void
    {
        $this->rejectAssigneeUnlessSupervisor($validator);
    }
}
