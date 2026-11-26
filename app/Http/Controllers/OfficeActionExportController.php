<?php

namespace App\Http\Controllers;

use App\CheckingStatus;
use App\Models\OfficeAction;
use App\OfficeActionList;
use App\OfficeActionLister;
use App\OfficeActionOutcome;
use App\OfficeActionPresenter;
use App\SpreadsheetExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OfficeActionExportController extends Controller
{
    public function index(
        Request $request,
        OfficeActionLister $lister,
        OfficeActionPresenter $presenter,
        SpreadsheetExporter $exporter,
    ): BinaryFileResponse {
        Gate::authorize('viewAny', OfficeAction::class);

        $user = $this->actor($request);
        $list = OfficeActionList::tryFrom($request->string('list')->toString()) ?? OfficeActionList::Mine;
        $clientId = $request->integer('client_id') ?: null;
        $assigneeId = $request->integer('assignee_id') ?: null;

        $officeActions = $lister->query($list, $user, $clientId, $assigneeId)->limit(5000)->get();

        $headers = [
            'Client',
            'Client reference number',
            'Application number',
            'Application name',
            'HCI reference',
            'Applicant names',
            'Paper number',
            'Office action type',
            'Mailing date',
            'Response period (months)',
            'Original deadline',
            'Extended deadline',
            'Extension',
            'Current deadline',
            'Internal response date',
            'Reminder date',
            'Assignee',
            'Status',
            'Checking',
            'Outcome',
            'Next step',
        ];

        $rows = [];

        foreach ($officeActions as $officeAction) {
            $summary = $presenter->summary($officeAction);

            $rows[] = [
                $this->cell($summary['client_name'] ?? null),
                $this->cell($summary['client_reference'] ?? null),
                $this->cell($summary['application_number'] ?? null),
                $this->cell($summary['application_name'] ?? null),
                $this->cell($summary['hci_reference'] ?? null),
                $this->cell($summary['applicant_names'] ?? null),
                $this->cell($summary['paper_number'] ?? null),
                $this->cell($summary['type_label'] ?? null),
                $this->cell($summary['mailing_date'] ?? null),
                $this->cell($summary['response_period_months'] ?? null),
                $this->cell($summary['original_deadline'] ?? null),
                $this->cell($summary['extended_deadline'] ?? null),
                $this->cell($summary['extension_label'] ?? null),
                $this->cell($summary['current_deadline'] ?? null),
                $this->cell($summary['internal_response_date'] ?? null),
                $this->cell($summary['reminder_date'] ?? null),
                $this->cell($summary['assignee_name'] ?? null),
                $this->cell($summary['status'] ?? null),
                $this->cell(CheckingStatus::tryFrom($this->cell($summary['checking_status'] ?? null))?->label()),
                $this->cell(OfficeActionOutcome::tryFrom($this->cell($summary['outcome'] ?? null))?->label()),
                $this->cell($summary['next_step'] ?? null),
            ];
        }

        $path = $exporter->toTempFile($headers, $rows);

        return response()->download(
            $path,
            'office-actions-'.$list->value.'-'.today()->toDateString().'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        )->deleteFileAfterSend(true);
    }

    private function cell(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return '';
    }
}
