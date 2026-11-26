<?php

namespace App\Http\Requests\Concerns;

use App\Models\User;
use App\OfficeActionType;
use App\Rules\CommaSeparatedNames;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Validator;

/**
 * @mixin FormRequest
 */
trait ValidatesOfficeActionDetails
{
    /**
     * @return array<string, mixed>
     */
    protected function officeActionRules(bool $allowNewClient, bool $allowAssignee): array
    {
        $clientRules = $allowNewClient
            ? ['nullable', 'integer', $this->sameFirm('clients'), 'required_without:new_client_name']
            : ['required', 'integer', $this->sameFirm('clients')];

        $rules = [
            'client_id' => $clientRules,
            'application_number' => ['nullable', 'string', 'max:100', 'required_without:hci_reference'],
            'hci_reference' => ['nullable', 'string', 'max:100', 'required_without:application_number'],
            'applicant_names' => ['required', 'string', 'max:2000', new CommaSeparatedNames],
            'paper_number' => ['required', 'string', 'max:50'],
            'type' => ['required', Rule::enum(OfficeActionType::class)],
            'mailing_date' => ['required', 'date'],
            'response_period_months' => ['required', 'integer', 'min:1', 'max:12'],
            'original_deadline' => ['nullable', 'date', 'after_or_equal:mailing_date'],
            'internal_response_date' => ['nullable', 'date'],
            'reminder_date' => ['nullable', 'date'],
        ];

        if ($allowNewClient) {
            $rules['new_client_name'] = ['nullable', 'string', 'max:255', 'required_without:client_id'];
        }

        if ($allowAssignee) {
            $rules['assignee_id'] = ['nullable', 'integer', $this->sameFirm('users')];
        }

        return $rules;
    }

    protected function sameFirm(string $table): Exists
    {
        $user = $this->user();

        return Rule::exists($table, 'id')->where(
            'organization_id',
            $user instanceof User ? $user->organization_id : 0,
        );
    }

    protected function prepareOfficeActionDetails(): void
    {
        $this->merge([
            'application_number' => $this->blankToNull('application_number'),
            'hci_reference' => $this->blankToNull('hci_reference'),
            'applicant_names' => $this->blankToNull('applicant_names'),
            'original_deadline' => $this->blankToNull('original_deadline'),
            'internal_response_date' => $this->blankToNull('internal_response_date'),
            'reminder_date' => $this->blankToNull('reminder_date'),
            'client_id' => $this->filled('client_id') ? $this->integer('client_id') : null,
            'assignee_id' => $this->filled('assignee_id') ? $this->integer('assignee_id') : null,
            'new_client_name' => $this->blankToNull('new_client_name'),
        ]);
    }

    protected function blankToNull(string $key): ?string
    {
        $value = $this->string($key)->trim()->toString();

        return $value === '' ? null : $value;
    }

    /**
     * @return array<string, string>
     */
    protected function officeActionMessages(): array
    {
        return [
            'application_number.required_without' => 'Enter an application number or an HCI reference.',
            'hci_reference.required_without' => 'Enter an application number or an HCI reference.',
            'applicant_names.required' => 'Enter the applicant name or names.',
            'client_id.required_without' => 'Choose a client or enter a new client name.',
            'new_client_name.required_without' => 'Choose a client or enter a new client name.',
        ];
    }

    protected function rejectAssigneeUnlessSupervisor(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->user();

            if ($this->filled('assignee_id') && (! $user instanceof User || ! $user->isSupervisor())) {
                $validator->errors()->add('assignee_id', 'Only a supervisor can assign an office action.');
            }
        });
    }
}
