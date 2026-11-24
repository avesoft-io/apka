<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesOfficeActionDetails;
use App\Models\OfficeAction;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOfficeActionRequest extends FormRequest
{
    use ValidatesOfficeActionDetails;

    public function authorize(): bool
    {
        $officeAction = $this->route('officeAction');

        return $officeAction instanceof OfficeAction
            && ($this->user()?->can('update', $officeAction) ?? false);
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
        return $this->officeActionRules(allowNewClient: false, allowAssignee: false);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->officeActionMessages();
    }
}
