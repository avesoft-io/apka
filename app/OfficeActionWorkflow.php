<?php

namespace App;

use App\Models\OfficeAction;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\ValidationException;

class OfficeActionWorkflow
{
    public function __construct(private ActivityLogger $activities) {}

    /**
     * A pending extension stays on the checklist without becoming the only next step.
     * Filing remains blocked until that extension is confirmed or withdrawn.
     *
     * @return list<array{key: string, label: string, detail: string, state: string}>
     */
    public function checklist(OfficeAction $officeAction): array
    {
        $officeAction->loadMissing('assignee');

        $steps = [
            $this->receiveStep($officeAction),
            $this->reportStep($officeAction),
            $this->instructionStep($officeAction),
        ];

        if ($officeAction->extension_status !== ExtensionStatus::None) {
            $steps[] = $this->extensionStep($officeAction);
        }

        $steps[] = $this->draftStep($officeAction);
        $steps[] = $this->checkingStep($officeAction);

        if ($officeAction->client_approval_required) {
            $steps[] = $this->approvalStep($officeAction);
        }

        $steps[] = $this->fileStep($officeAction);
        $steps[] = $this->filingReportStep($officeAction);

        return $this->markStates($officeAction, $steps);
    }

    public function statusLabel(OfficeAction $officeAction): string
    {
        if ($officeAction->outcome === OfficeActionOutcome::Abandoned) {
            return 'Abandoned';
        }

        if ($officeAction->outcome === OfficeActionOutcome::Completed) {
            return 'Completed';
        }

        $label = 'Open';

        foreach ($this->checklist($officeAction) as $step) {
            if ($step['state'] !== 'current') {
                continue;
            }

            $label = match ($step['key']) {
                'receive_assign' => 'Needs assignment',
                'report_client' => 'Report to client',
                'await_instructions' => 'Waiting for instructions',
                'draft' => 'Draft the response',
                'checking' => $this->checkingLabel($officeAction),
                'client_approval' => 'Waiting for client approval',
                'file' => $this->filingBlockers($officeAction) === [] ? 'Ready to file' : 'File the response',
                'report_filing' => 'Filed, pending reporting',
                default => $step['label'],
            };

            break;
        }

        if ($officeAction->extension_status === ExtensionStatus::Pending && $officeAction->filing_date === null) {
            return $label.' · Extension pending';
        }

        return $label;
    }

    public function nextStep(OfficeAction $officeAction): ?string
    {
        if ($officeAction->outcome === OfficeActionOutcome::Abandoned) {
            return $officeAction->abandon_reason;
        }

        foreach ($this->checklist($officeAction) as $step) {
            if ($step['state'] === 'current') {
                return $step['detail'];
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function filingBlockers(OfficeAction $officeAction): array
    {
        $blockers = [];

        if ($officeAction->assignee_id === null) {
            $blockers[] = 'Assign the office action before filing.';
        }

        if ($officeAction->report_sent_on === null) {
            $blockers[] = 'Record that the office action was reported to the client.';
        }

        if (blank($officeAction->response_instructions)) {
            $blockers[] = 'Record the client\'s instructions.';
        }

        if ($officeAction->extension_status === ExtensionStatus::Pending) {
            $blockers[] = 'Confirm or withdraw the extension before filing.';
        }

        if ($officeAction->draft_prepared_on === null) {
            $blockers[] = 'Mark the draft as prepared.';
        }

        if ($officeAction->checking_status !== CheckingStatus::Checked) {
            $blockers[] = 'The draft has to be checked before filing.';
        }

        if ($officeAction->client_approval_required && $officeAction->client_approval_status !== ClientApprovalStatus::Approved) {
            $blockers[] = 'Record the client\'s approval of the draft.';
        }

        return $blockers;
    }

    public function assign(OfficeAction $officeAction, User $actor, ?int $assigneeId): void
    {
        $this->ensureOpen($officeAction);

        $officeAction->fill([
            'assignee_id' => $assigneeId,
            'assigned_by_id' => $assigneeId === null ? null : $actor->id,
        ]);

        $this->activities->updated($officeAction, $actor, $officeAction->client_id);
    }

    public function recordReport(
        OfficeAction $officeAction,
        User $actor,
        CarbonInterface $sentOn,
        bool $includesFees,
        bool $requestsInstructions,
    ): void {
        $this->ensureOpen($officeAction);

        if ($officeAction->assignee_id === null) {
            throw ValidationException::withMessages([
                'report_sent_on' => 'Assign the office action before reporting it to the client.',
            ]);
        }

        $officeAction->fill([
            'report_sent_on' => $sentOn->toDateString(),
            'report_includes_fees' => $includesFees,
            'report_requests_instructions' => $requestsInstructions,
        ]);

        $this->activities->updated($officeAction, $actor, $officeAction->client_id);
    }

    public function recordInstructions(
        OfficeAction $officeAction,
        User $actor,
        string $instructions,
        CarbonInterface $receivedOn,
    ): void {
        $this->ensureOpen($officeAction);

        if ($officeAction->report_sent_on === null) {
            throw ValidationException::withMessages([
                'response_instructions' => 'Record the client report before logging instructions.',
            ]);
        }

        $officeAction->fill([
            'response_instructions' => $instructions,
            'instructions_received_on' => $receivedOn->toDateString(),
        ]);

        $this->activities->updated($officeAction, $actor, $officeAction->client_id);
    }

    public function requestExtension(OfficeAction $officeAction, User $actor, CarbonInterface $requestedOn): void
    {
        $this->ensureOpen($officeAction);

        if ($officeAction->filing_date !== null) {
            throw ValidationException::withMessages([
                'extension_requested_on' => 'The response has already been filed.',
            ]);
        }

        $officeAction->fill([
            'extension_status' => ExtensionStatus::Pending,
            'extension_requested_on' => $requestedOn->toDateString(),
        ]);

        $this->activities->updated($officeAction, $actor, $officeAction->client_id);
    }

    public function confirmExtension(OfficeAction $officeAction, User $actor, CarbonInterface $extendedDeadline): void
    {
        $this->ensureOpen($officeAction);

        if ($officeAction->extension_status !== ExtensionStatus::Pending) {
            throw ValidationException::withMessages([
                'extended_deadline' => 'Request the extension before confirming it.',
            ]);
        }

        $extended = Date::parse($extendedDeadline)->startOfDay();
        $original = $officeAction->original_deadline->copy()->startOfDay();

        if ($extended->lessThanOrEqualTo($original)) {
            throw ValidationException::withMessages([
                'extended_deadline' => 'The extended deadline must be after the original deadline.',
            ]);
        }

        $officeAction->fill([
            'extension_status' => ExtensionStatus::Confirmed,
            'extended_deadline' => $extended->toDateString(),
        ]);

        $this->activities->updated($officeAction, $actor, $officeAction->client_id);
    }

    public function withdrawExtension(OfficeAction $officeAction, User $actor): void
    {
        $this->ensureOpen($officeAction);

        if ($officeAction->extension_status !== ExtensionStatus::Pending) {
            throw ValidationException::withMessages([
                'extension' => 'There is no pending extension to withdraw.',
            ]);
        }

        if ($officeAction->extended_deadline !== null) {
            $officeAction->extension_status = ExtensionStatus::Confirmed;
        } else {
            $officeAction->fill([
                'extension_status' => ExtensionStatus::None,
                'extension_requested_on' => null,
            ]);
        }

        $this->activities->updated($officeAction, $actor, $officeAction->client_id);
    }

    public function prepareDraft(
        OfficeAction $officeAction,
        User $actor,
        bool $responsePrepared,
        bool $amendedClaimsPrepared,
        bool $otherDocumentsPrepared,
        ?string $notes,
        CarbonInterface $preparedOn,
    ): void {
        $this->ensureOpen($officeAction);

        if (blank($officeAction->response_instructions)) {
            throw ValidationException::withMessages([
                'draft_prepared_on' => 'Record the client\'s instructions before drafting the response.',
            ]);
        }

        if (! $responsePrepared && ! $amendedClaimsPrepared && ! $otherDocumentsPrepared) {
            throw ValidationException::withMessages([
                'response_prepared' => 'Mark the response, amended claims, or other documents as prepared.',
            ]);
        }

        $officeAction->fill([
            'response_prepared' => $responsePrepared,
            'amended_claims_prepared' => $amendedClaimsPrepared,
            'other_documents_prepared' => $otherDocumentsPrepared,
            'draft_notes' => $notes,
            'draft_prepared_on' => $preparedOn->toDateString(),
        ]);

        $this->activities->updated($officeAction, $actor, $officeAction->client_id);
    }

    public function setCheckingStatus(OfficeAction $officeAction, User $actor, CheckingStatus $status): void
    {
        $this->ensureOpen($officeAction);

        if ($officeAction->draft_prepared_on === null) {
            throw ValidationException::withMessages([
                'checking_status' => 'Prepare the draft before sending it for checking.',
            ]);
        }

        if ($status === CheckingStatus::None) {
            throw ValidationException::withMessages([
                'checking_status' => 'Choose for checking, for revision, or checked.',
            ]);
        }

        $officeAction->checking_status = $status;
        $this->activities->updated($officeAction, $actor, $officeAction->client_id);
    }

    public function setClientApproval(OfficeAction $officeAction, User $actor, bool $required, bool $approved): void
    {
        $this->ensureOpen($officeAction);

        if ($officeAction->draft_prepared_on === null) {
            throw ValidationException::withMessages([
                'client_approval_required' => 'Prepare the draft before requesting client approval.',
            ]);
        }

        $status = ClientApprovalStatus::NotRequired;

        if ($required) {
            $status = $approved ? ClientApprovalStatus::Approved : ClientApprovalStatus::Pending;
        }

        $officeAction->fill([
            'client_approval_required' => $required,
            'client_approval_status' => $status,
        ]);

        $this->activities->updated($officeAction, $actor, $officeAction->client_id);
    }

    public function file(OfficeAction $officeAction, User $actor, CarbonInterface $filingDate): void
    {
        $this->ensureOpen($officeAction);

        $blockers = $this->filingBlockers($officeAction);

        if ($blockers !== []) {
            throw ValidationException::withMessages([
                'filing_date' => implode(' ', $blockers),
            ]);
        }

        $officeAction->filing_date = Date::parse($filingDate);
        $this->activities->updated($officeAction, $actor, $officeAction->client_id);
    }

    public function reportFiling(OfficeAction $officeAction, User $actor, CarbonInterface $reportedOn): void
    {
        $this->ensureOpen($officeAction);

        if ($officeAction->filing_date === null) {
            throw ValidationException::withMessages([
                'filing_reported_on' => 'Enter the filing date before reporting it to the client.',
            ]);
        }

        $officeAction->fill([
            'filing_reported_on' => $reportedOn->toDateString(),
            'outcome' => OfficeActionOutcome::Completed,
        ]);

        $this->activities->updated($officeAction, $actor, $officeAction->client_id);
    }

    public function abandon(OfficeAction $officeAction, User $actor, string $instruction, string $reason): void
    {
        $this->ensureOpen($officeAction);

        if ($officeAction->filing_date !== null) {
            throw ValidationException::withMessages([
                'reason' => 'This response has already been filed.',
            ]);
        }

        $officeAction->fill([
            'outcome' => OfficeActionOutcome::Abandoned,
            'abandoned_at' => now(),
            'abandon_reason' => $reason,
            'response_instructions' => $instruction,
            'instructions_received_on' => $officeAction->instructions_received_on ?? today()->toDateString(),
        ]);

        $this->activities->updated($officeAction, $actor, $officeAction->client_id);
    }

    public function markReminderSent(OfficeAction $officeAction, User $actor): void
    {
        $this->ensureOpen($officeAction);

        if ($officeAction->reminder_date === null) {
            throw ValidationException::withMessages([
                'reminder' => 'Set a reminder date first.',
            ]);
        }

        $officeAction->fill([
            'reminder_sent_on' => today()->toDateString(),
            'reminder_sent_for' => $officeAction->reminder_date->toDateString(),
        ]);

        $this->activities->updated($officeAction, $actor, $officeAction->client_id);
    }

    /**
     * @param  list<array{key: string, label: string, detail: string, complete: bool}>  $steps
     * @return list<array{key: string, label: string, detail: string, state: string}>
     */
    private function markStates(OfficeAction $officeAction, array $steps): array
    {
        $currentKey = null;

        if ($officeAction->outcome === OfficeActionOutcome::Open) {
            foreach ($steps as $step) {
                if ($step['key'] === 'extension' || $step['complete']) {
                    continue;
                }

                $currentKey = $step['key'];
                break;
            }
        }

        $marked = [];

        foreach ($steps as $step) {
            $state = 'upcoming';

            if ($officeAction->outcome === OfficeActionOutcome::Abandoned && ! $step['complete']) {
                $state = 'skipped';
            } elseif ($step['key'] === 'extension') {
                $state = $step['complete'] ? 'done' : 'attention';
            } elseif ($step['complete']) {
                $state = 'done';
            } elseif ($step['key'] === $currentKey) {
                $state = 'current';
            }

            $marked[] = [
                'key' => $step['key'],
                'label' => $step['label'],
                'detail' => $step['detail'],
                'state' => $state,
            ];
        }

        return $marked;
    }

    /**
     * @return array{key: string, label: string, detail: string, complete: bool}
     */
    private function receiveStep(OfficeAction $officeAction): array
    {
        $assigned = $officeAction->assignee_id !== null;

        return [
            'key' => 'receive_assign',
            'label' => 'Receive and assign',
            'detail' => $assigned
                ? 'Assigned to '.($officeAction->assignee instanceof User ? $officeAction->assignee->name : 'a staff member').'.'
                : 'Logged. A supervisor still needs to assign it.',
            'complete' => $assigned,
        ];
    }

    /**
     * @return array{key: string, label: string, detail: string, complete: bool}
     */
    private function reportStep(OfficeAction $officeAction): array
    {
        $sent = $officeAction->report_sent_on !== null;
        $notes = [];

        if ($officeAction->report_includes_fees) {
            $notes[] = 'fees included';
        }

        if ($officeAction->report_requests_instructions) {
            $notes[] = 'instructions requested';
        }

        $suffix = $notes === [] ? '' : ' '.implode(', ', $notes).'.';

        return [
            'key' => 'report_client',
            'label' => 'Report the office action to the client',
            'detail' => $sent
                ? 'Sent '.$this->displayDate($officeAction->report_sent_on).'.'.$suffix
                : 'Prepare the report, include fees or request instructions as needed, and record when it was sent.',
            'complete' => $sent,
        ];
    }

    /**
     * @return array{key: string, label: string, detail: string, complete: bool}
     */
    private function instructionStep(OfficeAction $officeAction): array
    {
        $recorded = filled($officeAction->response_instructions);

        return [
            'key' => 'await_instructions',
            'label' => 'Wait for instructions and follow up',
            'detail' => $recorded
                ? 'Instructions recorded '.$this->displayDate($officeAction->instructions_received_on).'.'
                : 'Set a reminder date and record the client\'s instructions when they come in.',
            'complete' => $recorded,
        ];
    }

    /**
     * @return array{key: string, label: string, detail: string, complete: bool}
     */
    private function extensionStep(OfficeAction $officeAction): array
    {
        $confirmed = $officeAction->extension_status === ExtensionStatus::Confirmed;

        $detail = 'Request filed '.$this->displayDate($officeAction->extension_requested_on).'. Confirm the extended deadline when it is granted.';

        if ($confirmed) {
            $detail = 'Confirmed. Extended deadline '.$this->displayDate($officeAction->extended_deadline).'. Original deadline '.$this->displayDate($officeAction->original_deadline).' stays on the record.';
        }

        return [
            'key' => 'extension',
            'label' => 'Request an extension',
            'detail' => $detail,
            'complete' => $confirmed,
        ];
    }

    /**
     * @return array{key: string, label: string, detail: string, complete: bool}
     */
    private function draftStep(OfficeAction $officeAction): array
    {
        $prepared = $officeAction->draft_prepared_on !== null;
        $parts = array_values(array_filter([
            $officeAction->response_prepared ? 'response' : null,
            $officeAction->amended_claims_prepared ? 'amended claims' : null,
            $officeAction->other_documents_prepared ? 'other documents' : null,
        ]));

        return [
            'key' => 'draft',
            'label' => 'Draft the response',
            'detail' => $prepared
                ? 'Prepared '.$this->displayDate($officeAction->draft_prepared_on).': '.implode(', ', $parts).'.'
                : 'Prepare the response, amended claims, and any other documents needed.',
            'complete' => $prepared,
        ];
    }

    /**
     * @return array{key: string, label: string, detail: string, complete: bool}
     */
    private function checkingStep(OfficeAction $officeAction): array
    {
        return [
            'key' => 'checking',
            'label' => 'Send the draft for checking',
            'detail' => $officeAction->checking_status->label().'.',
            'complete' => $officeAction->checking_status === CheckingStatus::Checked,
        ];
    }

    /**
     * @return array{key: string, label: string, detail: string, complete: bool}
     */
    private function approvalStep(OfficeAction $officeAction): array
    {
        $approved = $officeAction->client_approval_status === ClientApprovalStatus::Approved;

        return [
            'key' => 'client_approval',
            'label' => 'Client approval of the draft',
            'detail' => $approved
                ? 'Client approved the draft.'
                : 'Waiting for the client to approve the draft.',
            'complete' => $approved,
        ];
    }

    /**
     * @return array{key: string, label: string, detail: string, complete: bool}
     */
    private function fileStep(OfficeAction $officeAction): array
    {
        if ($officeAction->filing_date !== null) {
            $detail = 'Filed '.$this->displayDate($officeAction->filing_date).'.';
        } elseif ($this->filingBlockers($officeAction) === []) {
            $detail = 'Ready to file. Enter the filing date once it is submitted.';
        } else {
            $detail = implode(' ', $this->filingBlockers($officeAction));
        }

        return [
            'key' => 'file',
            'label' => 'File the response',
            'detail' => $detail,
            'complete' => $officeAction->filing_date !== null,
        ];
    }

    /**
     * @return array{key: string, label: string, detail: string, complete: bool}
     */
    private function filingReportStep(OfficeAction $officeAction): array
    {
        $reported = $officeAction->filing_reported_on !== null;

        if ($reported) {
            $detail = 'Filing report sent '.$this->displayDate($officeAction->filing_reported_on).'.';
        } elseif ($officeAction->filing_date !== null) {
            $detail = 'Filed, pending reporting.';
        } else {
            $detail = 'After filing, report it to the client.';
        }

        return [
            'key' => 'report_filing',
            'label' => 'Report the filing to the client',
            'detail' => $detail,
            'complete' => $reported,
        ];
    }

    private function checkingLabel(OfficeAction $officeAction): string
    {
        return match ($officeAction->checking_status) {
            CheckingStatus::ForChecking => 'For checking',
            CheckingStatus::ForRevision => 'For revision',
            CheckingStatus::Checked => 'Checked',
            CheckingStatus::None => 'Send the draft for checking',
        };
    }

    private function displayDate(mixed $value): string
    {
        if ($value instanceof CarbonInterface) {
            return $value->format('j M Y');
        }

        return 'an unknown date';
    }

    private function ensureOpen(OfficeAction $officeAction): void
    {
        if ($officeAction->outcome !== OfficeActionOutcome::Open) {
            throw ValidationException::withMessages([
                'office_action' => 'This office action is already closed.',
            ]);
        }
    }
}
