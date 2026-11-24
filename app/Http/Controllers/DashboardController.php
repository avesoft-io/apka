<?php

namespace App\Http\Controllers;

use App\Models\OfficeAction;
use App\OfficeActionList;
use App\OfficeActionLister;
use App\OfficeActionPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request, OfficeActionLister $lister, OfficeActionPresenter $presenter): Response
    {
        $user = $this->actor($request);

        $upcoming = OfficeAction::query()
            ->with(['client', 'assignee'])
            ->open()
            ->assignedTo($user)
            ->whereNull('filing_date')
            ->orderByCurrentDeadline()
            ->limit(8)
            ->get()
            ->map(fn (OfficeAction $officeAction): array => $presenter->summary($officeAction))
            ->all();

        $reminders = OfficeAction::query()
            ->with(['client', 'assignee'])
            ->followUps()
            ->assignedTo($user)
            ->orderByCurrentDeadline()
            ->limit(20)
            ->get()
            ->map(fn (OfficeAction $officeAction): array => $presenter->summary($officeAction))
            ->all();

        $lists = collect(OfficeActionList::cases())
            ->reject(fn (OfficeActionList $list): bool => $list === OfficeActionList::All)
            ->map(fn (OfficeActionList $list): array => [
                'value' => $list->value,
                'label' => $list->label(),
                'description' => $list->description(),
                'count' => $lister->count($list, $user),
            ])
            ->values()
            ->all();

        return Inertia::render('Dashboard', [
            'upcoming' => $upcoming,
            'reminders' => $reminders,
            'lists' => $lists,
        ]);
    }
}
