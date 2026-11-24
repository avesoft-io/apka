<?php

namespace App\Http\Requests;

use App\Models\OfficeAction;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignOfficeActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $officeAction = $this->route('officeAction');

        return $officeAction instanceof OfficeAction
            && ($this->user()?->can('assign', $officeAction) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'assignee_id' => $this->filled('assignee_id') ? $this->integer('assignee_id') : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();

        return [
            'assignee_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where(
                'organization_id',
                $user instanceof User ? $user->organization_id : 0,
            )],
        ];
    }

    public function assigneeId(): ?int
    {
        $assigneeId = $this->validated('assignee_id');

        if ($assigneeId === null || $assigneeId === '') {
            return null;
        }

        return (int) $assigneeId;
    }
}
