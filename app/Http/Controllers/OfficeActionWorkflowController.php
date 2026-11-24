<?php

namespace App\Http\Controllers;

use App\CheckingStatus;
use App\Models\OfficeAction;
use App\OfficeActionWorkflow;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class OfficeActionWorkflowController extends Controller
{
    public function report(Request $request, OfficeAction $officeAction, OfficeActionWorkflow $workflow): RedirectResponse
    {
        Gate::authorize('update', $officeAction);

        $data = $request->validate([
            'report_sent_on' => ['required', 'date'],
            'report_includes_fees' => ['required', 'boolean'],
            'report_requests_instructions' => ['required', 'boolean'],
        ]);

        $workflow->recordReport(
            $officeAction,
            $this->actor($request),
            $this->day($data['report_sent_on']),
            (bool) $data['report_includes_fees'],
            (bool) $data['report_requests_instructions'],
        );

        return $this->backTo($officeAction, 'Client report recorded.');
    }

    public function instructions(Request $request, OfficeAction $officeAction, OfficeActionWorkflow $workflow): RedirectResponse
    {
        Gate::authorize('update', $officeAction);

        $data = $request->validate([
            'response_instructions' => ['required', 'string', 'max:5000'],
            'instructions_received_on' => ['required', 'date'],
        ]);

        $workflow->recordInstructions(
            $officeAction,
            $this->actor($request),
            (string) $data['response_instructions'],
            $this->day($data['instructions_received_on']),
        );

        return $this->backTo($officeAction, 'Client instructions recorded.');
    }

    public function requestExtension(Request $request, OfficeAction $officeAction, OfficeActionWorkflow $workflow): RedirectResponse
    {
        Gate::authorize('update', $officeAction);

        $data = $request->validate([
            'extension_requested_on' => ['required', 'date'],
        ]);

        $workflow->requestExtension(
            $officeAction,
            $this->actor($request),
            $this->day($data['extension_requested_on']),
        );

        return $this->backTo($officeAction, 'Extension request recorded.');
    }

    public function confirmExtension(Request $request, OfficeAction $officeAction, OfficeActionWorkflow $workflow): RedirectResponse
    {
        Gate::authorize('update', $officeAction);

        $data = $request->validate([
            'extended_deadline' => ['required', 'date'],
        ]);

        $workflow->confirmExtension(
            $officeAction,
            $this->actor($request),
            $this->day($data['extended_deadline']),
        );

        return $this->backTo($officeAction, 'Extended deadline confirmed.');
    }

    public function withdrawExtension(Request $request, OfficeAction $officeAction, OfficeActionWorkflow $workflow): RedirectResponse
    {
        Gate::authorize('update', $officeAction);

        $workflow->withdrawExtension($officeAction, $this->actor($request));

        return $this->backTo($officeAction, 'Extension request updated.');
    }

    public function draft(Request $request, OfficeAction $officeAction, OfficeActionWorkflow $workflow): RedirectResponse
    {
        Gate::authorize('update', $officeAction);

        $data = $request->validate([
            'response_prepared' => ['required', 'boolean'],
            'amended_claims_prepared' => ['required', 'boolean'],
            'other_documents_prepared' => ['required', 'boolean'],
            'draft_notes' => ['nullable', 'string', 'max:5000'],
            'draft_prepared_on' => ['required', 'date'],
        ]);

        $notes = $data['draft_notes'] ?? null;

        $workflow->prepareDraft(
            $officeAction,
            $this->actor($request),
            (bool) $data['response_prepared'],
            (bool) $data['amended_claims_prepared'],
            (bool) $data['other_documents_prepared'],
            is_string($notes) && $notes !== '' ? $notes : null,
            $this->day($data['draft_prepared_on']),
        );

        return $this->backTo($officeAction, 'Draft recorded.');
    }

    public function checking(Request $request, OfficeAction $officeAction, OfficeActionWorkflow $workflow): RedirectResponse
    {
        Gate::authorize('update', $officeAction);

        $data = $request->validate([
            'checking_status' => ['required', Rule::in([
                CheckingStatus::ForChecking->value,
                CheckingStatus::ForRevision->value,
                CheckingStatus::Checked->value,
            ])],
        ]);

        $workflow->setCheckingStatus(
            $officeAction,
            $this->actor($request),
            CheckingStatus::from((string) $data['checking_status']),
        );

        return $this->backTo($officeAction, 'Checking status saved.');
    }

    public function approval(Request $request, OfficeAction $officeAction, OfficeActionWorkflow $workflow): RedirectResponse
    {
        Gate::authorize('update', $officeAction);

        $data = $request->validate([
            'client_approval_required' => ['required', 'boolean'],
            'approved' => ['required', 'boolean'],
        ]);

        $workflow->setClientApproval(
            $officeAction,
            $this->actor($request),
            (bool) $data['client_approval_required'],
            (bool) $data['approved'],
        );

        return $this->backTo($officeAction, 'Client approval saved.');
    }

    public function filing(Request $request, OfficeAction $officeAction, OfficeActionWorkflow $workflow): RedirectResponse
    {
        Gate::authorize('update', $officeAction);

        $data = $request->validate([
            'filing_date' => ['required', 'date'],
        ]);

        $workflow->file($officeAction, $this->actor($request), $this->day($data['filing_date']));

        return $this->backTo($officeAction, 'Filing date recorded.');
    }

    public function filingReport(Request $request, OfficeAction $officeAction, OfficeActionWorkflow $workflow): RedirectResponse
    {
        Gate::authorize('update', $officeAction);

        $data = $request->validate([
            'filing_reported_on' => ['required', 'date'],
        ]);

        $workflow->reportFiling(
            $officeAction,
            $this->actor($request),
            $this->day($data['filing_reported_on']),
        );

        return $this->backTo($officeAction, 'Filing report recorded. This office action is completed.');
    }

    public function abandon(Request $request, OfficeAction $officeAction, OfficeActionWorkflow $workflow): RedirectResponse
    {
        Gate::authorize('update', $officeAction);

        $data = $request->validate([
            'instruction' => ['required', 'string', 'max:5000'],
            'reason' => ['required', 'string', 'max:5000'],
        ]);

        $workflow->abandon(
            $officeAction,
            $this->actor($request),
            (string) $data['instruction'],
            (string) $data['reason'],
        );

        return $this->backTo($officeAction, 'Office action closed as abandoned.');
    }

    public function reminder(Request $request, OfficeAction $officeAction, OfficeActionWorkflow $workflow): RedirectResponse
    {
        Gate::authorize('update', $officeAction);

        $workflow->markReminderSent($officeAction, $this->actor($request));

        return $this->backTo($officeAction, 'Reminder marked as sent.');
    }

    private function day(mixed $value): CarbonInterface
    {
        return Date::parse(is_scalar($value) ? (string) $value : '');
    }

    private function backTo(OfficeAction $officeAction, string $message): RedirectResponse
    {
        return redirect()
            ->route('office-actions.show', $officeAction)
            ->with('success', $message);
    }
}
