<?php

namespace App;

use App\Models\Activity;
use App\Models\Client;
use App\Models\ClientInstruction;
use App\Models\OfficeAction;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

class OfficeActionPresenter
{
    public function __construct(private OfficeActionWorkflow $workflow) {}

    /**
     * @return array<string, mixed>
     */
    public function summary(OfficeAction $officeAction): array
    {
        $officeAction->loadMissing(['client', 'assignee']);
        $deadline = $officeAction->currentDeadline();
        $overdue = $officeAction->outcome === OfficeActionOutcome::Open
            && $officeAction->filing_date === null
            && $deadline->copy()->startOfDay()->lt(today());

        return [
            'id' => $officeAction->id,
            'client_id' => $officeAction->client_id,
            'client_name' => $officeAction->client->name,
            'client_reference' => $officeAction->client_reference,
            'application_number' => $officeAction->application_number,
            'application_name' => $officeAction->application_name,
            'hci_reference' => $officeAction->hci_reference,
            'applicant_names' => $this->applicantNames($officeAction->applicant_names),
            'reference' => $officeAction->referenceLabel(),
            'paper_number' => $officeAction->paper_number,
            'type' => $officeAction->type->value,
            'type_label' => $officeAction->type->label(),
            'mailing_date' => $this->date($officeAction->mailing_date),
            'response_period_months' => $officeAction->response_period_months,
            'original_deadline' => $this->date($officeAction->original_deadline),
            'extended_deadline' => $this->date($officeAction->extended_deadline),
            'current_deadline' => $this->date($deadline),
            'extension_status' => $officeAction->extension_status->value,
            'extension_label' => $officeAction->extension_status->label(),
            'internal_response_date' => $this->date($officeAction->internal_response_date),
            'reminder_date' => $this->date($officeAction->reminder_date),
            'assignee_id' => $officeAction->assignee_id,
            'assignee_name' => $officeAction->assignee?->name,
            'status' => $this->workflow->statusLabel($officeAction),
            'next_step' => $this->workflow->nextStep($officeAction),
            'outcome' => $officeAction->outcome->value,
            'due_label' => $this->dueLabel($officeAction, $deadline),
            'overdue' => $overdue,
            'checking_status' => $officeAction->checking_status->value,
        ];
    }

    /**
     * @param  Collection<int, Activity>  $activities
     * @return array<string, mixed>
     */
    public function detail(OfficeAction $officeAction, Collection $activities): array
    {
        $officeAction->loadMissing(['client.instructions.author']);

        return [
            ...$this->summary($officeAction),
            'report_sent_on' => $this->date($officeAction->report_sent_on),
            'report_includes_fees' => $officeAction->report_includes_fees,
            'report_requests_instructions' => $officeAction->report_requests_instructions,
            'response_instructions' => $officeAction->response_instructions,
            'instructions_received_on' => $this->date($officeAction->instructions_received_on),
            'extension_requested_on' => $this->date($officeAction->extension_requested_on),
            'draft_notes' => $officeAction->draft_notes,
            'response_prepared' => $officeAction->response_prepared,
            'amended_claims_prepared' => $officeAction->amended_claims_prepared,
            'other_documents_prepared' => $officeAction->other_documents_prepared,
            'draft_prepared_on' => $this->date($officeAction->draft_prepared_on),
            'client_approval_required' => $officeAction->client_approval_required,
            'client_approval_status' => $officeAction->client_approval_status->value,
            'filing_date' => $this->date($officeAction->filing_date),
            'filing_reported_on' => $this->date($officeAction->filing_reported_on),
            'abandon_reason' => $officeAction->abandon_reason,
            'checklist' => $this->workflow->checklist($officeAction),
            'filing_blockers' => $this->workflow->filingBlockers($officeAction),
            'standing_instructions' => $officeAction->client->instructions
                ->sortBy('id')
                ->values()
                ->map(fn (ClientInstruction $instruction): array => [
                    'id' => $instruction->id,
                    'body' => $instruction->body,
                    'author' => $instruction->author?->name,
                    'created_at' => $instruction->created_at?->format('j M Y'),
                ])
                ->all(),
            'history' => $this->history($activities),
        ];
    }

    /**
     * @param  Collection<int, Activity>  $activities
     * @return list<array{id: int, event: string, user: string, when: string, entries: list<array{field: string, before: string, after: string}>}>
     */
    public function history(Collection $activities): array
    {
        $userIds = [];
        $clientIds = [];

        foreach ($activities as $activity) {
            foreach ($activity->changes as $field => $change) {
                if (! is_array($change)) {
                    continue;
                }

                foreach (['before', 'after'] as $side) {
                    $value = $change[$side] ?? null;

                    if (! is_numeric($value)) {
                        continue;
                    }

                    if (in_array($field, ['assignee_id', 'assigned_by_id', 'logged_by_id', 'created_by_id', 'user_id'], true)) {
                        $userIds[] = (int) $value;
                    }

                    if ($field === 'client_id') {
                        $clientIds[] = (int) $value;
                    }
                }
            }
        }

        $users = User::query()->whereIn('id', array_unique($userIds))->pluck('name', 'id');
        $clients = Client::query()->whereIn('id', array_unique($clientIds))->pluck('name', 'id');

        $history = [];

        foreach ($activities as $activity) {
            $entries = [];

            foreach ($activity->changes as $field => $change) {
                if (! is_array($change) || ! array_key_exists('before', $change) || ! array_key_exists('after', $change)) {
                    continue;
                }

                $entries[] = [
                    'field' => $this->fieldLabel($field, (string) $activity->subject_type, $activity->event),
                    'before' => $this->displayValue($field, $change['before'], $users, $clients),
                    'after' => $this->displayValue($field, $change['after'], $users, $clients),
                ];
            }

            $actor = $activity->user;

            $history[] = [
                'id' => $activity->id,
                'event' => $activity->event,
                'user' => $actor instanceof User ? $actor->name : 'Someone',
                'when' => $activity->created_at?->timezone(config('app.timezone'))->format('j M Y, H:i') ?? '',
                'entries' => $entries,
            ];
        }

        return $history;
    }

    /**
     * @param  list<string>|null  $names
     */
    private function applicantNames(?array $names): ?string
    {
        if ($names === null || $names === []) {
            return null;
        }

        return implode(', ', $names);
    }

    private function date(mixed $value): ?string
    {
        if ($value instanceof CarbonInterface) {
            return $value->toDateString();
        }

        return null;
    }

    private function dueLabel(OfficeAction $officeAction, CarbonInterface $deadline): ?string
    {
        if ($officeAction->outcome !== OfficeActionOutcome::Open || $officeAction->filing_date !== null) {
            return null;
        }

        $days = (int) today()->diffInDays($deadline->copy()->startOfDay(), false);

        if ($days < 0) {
            $count = abs($days);

            return $count === 1 ? 'Overdue by 1 day' : 'Overdue by '.$count.' days';
        }

        if ($days === 0) {
            return 'Due today';
        }

        if ($days === 1) {
            return 'Due tomorrow';
        }

        return 'Due in '.$days.' days';
    }

    private function fieldLabel(string $field, string $subjectType, string $event): string
    {
        if ($event === 'deleted' || ($field === 'body' && $subjectType === ClientInstruction::class)) {
            return 'Standing instruction';
        }

        return self::LABELS[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }

    /**
     * @param  Collection<int|string, string>  $users
     * @param  Collection<int|string, string>  $clients
     */
    private function displayValue(string $field, mixed $value, Collection $users, Collection $clients): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (in_array($field, self::BOOLEAN_FIELDS, true)) {
            $truthy = $value === true || $value === 1 || $value === '1';

            return $truthy ? 'Yes' : 'No';
        }

        if (in_array($field, ['assignee_id', 'assigned_by_id', 'logged_by_id', 'created_by_id', 'user_id'], true) && is_numeric($value)) {
            return $users->get((int) $value) ?? 'User #'.$value;
        }

        if ($field === 'client_id' && is_numeric($value)) {
            return $clients->get((int) $value) ?? 'Client #'.$value;
        }

        if ($field === 'type' && is_string($value)) {
            return OfficeActionType::tryFrom($value)?->label() ?? $value;
        }

        if ($field === 'extension_status' && is_string($value)) {
            return ExtensionStatus::tryFrom($value)?->label() ?? $value;
        }

        if ($field === 'checking_status' && is_string($value)) {
            return CheckingStatus::tryFrom($value)?->label() ?? $value;
        }

        if ($field === 'client_approval_status' && is_string($value)) {
            return ClientApprovalStatus::tryFrom($value)?->label() ?? $value;
        }

        if ($field === 'outcome' && is_string($value)) {
            return OfficeActionOutcome::tryFrom($value)?->label() ?? $value;
        }

        if ($field === 'role' && is_string($value)) {
            return UserRole::tryFrom($value)?->label() ?? $value;
        }

        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return Date::parse($value)->format('j M Y');
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_string($value)) {
            return $value;
        }

        return '—';
    }

    /**
     * @var array<string, string>
     */
    private const LABELS = [
        'client_id' => 'Client',
        'client_reference' => 'Client reference number',
        'application_number' => 'Application number',
        'application_name' => 'Application name',
        'hci_reference' => 'HCI reference',
        'applicant_names' => 'Applicant names',
        'paper_number' => 'Paper number',
        'type' => 'Office action type',
        'mailing_date' => 'Mailing date',
        'response_period_months' => 'Response period (months)',
        'original_deadline' => 'Original deadline',
        'internal_response_date' => 'Internal response date',
        'reminder_date' => 'Reminder date',
        'reminder_sent_on' => 'Reminder sent on',
        'reminder_sent_for' => 'Reminder covered',
        'extended_deadline' => 'Extended deadline',
        'extension_status' => 'Extension',
        'extension_requested_on' => 'Extension requested on',
        'assignee_id' => 'Assignee',
        'assigned_by_id' => 'Assigned by',
        'logged_by_id' => 'Logged by',
        'outcome' => 'Outcome',
        'abandon_reason' => 'Reason for closing',
        'response_instructions' => 'Instructions for this response',
        'instructions_received_on' => 'Instructions received on',
        'report_sent_on' => 'Report sent on',
        'report_includes_fees' => 'Report includes fees',
        'report_requests_instructions' => 'Report requests instructions',
        'draft_notes' => 'Draft notes',
        'response_prepared' => 'Response prepared',
        'amended_claims_prepared' => 'Amended claims prepared',
        'other_documents_prepared' => 'Other documents prepared',
        'draft_prepared_on' => 'Draft prepared on',
        'checking_status' => 'Checking',
        'client_approval_required' => 'Client approval required',
        'client_approval_status' => 'Client approval',
        'filing_date' => 'Filing date',
        'filing_reported_on' => 'Filing reported on',
        'name' => 'Name',
        'code' => 'Code',
        'record' => 'Record',
    ];

    /**
     * @var list<string>
     */
    private const BOOLEAN_FIELDS = [
        'report_includes_fees',
        'report_requests_instructions',
        'response_prepared',
        'amended_claims_prepared',
        'other_documents_prepared',
        'client_approval_required',
    ];
}
