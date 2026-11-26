<?php

namespace App\Http\Controllers;

use App\ActivityLogger;
use App\EmailDraftRenderer;
use App\Http\Requests\StoreOfficeActionRequest;
use App\Http\Requests\UpdateOfficeActionRequest;
use App\Models\Activity;
use App\Models\Client;
use App\Models\EmailTemplate;
use App\Models\OfficeAction;
use App\Models\User;
use App\OfficeActionDetails;
use App\OfficeActionList;
use App\OfficeActionLister;
use App\OfficeActionPresenter;
use App\OfficeActionType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OfficeActionController extends Controller
{
    public function index(Request $request, OfficeActionLister $lister, OfficeActionPresenter $presenter): Response
    {
        Gate::authorize('viewAny', OfficeAction::class);

        $user = $this->actor($request);
        $list = OfficeActionList::tryFrom($request->string('list')->toString()) ?? OfficeActionList::Mine;
        $clientId = $request->integer('client_id') ?: null;
        $assigneeId = $request->integer('assignee_id') ?: null;

        $officeActions = $lister->query($list, $user, $clientId, $assigneeId)
            ->paginate(20)
            ->withQueryString()
            ->through(fn (OfficeAction $officeAction): array => $presenter->summary($officeAction));

        return Inertia::render('OfficeActions/Index', [
            'list' => $list->value,
            'listLabel' => $list->label(),
            'listDescription' => $list->description(),
            'officeActions' => $officeActions,
            'filters' => [
                'client_id' => $clientId,
                'assignee_id' => $assigneeId,
            ],
            'lists' => collect(OfficeActionList::cases())->map(fn (OfficeActionList $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
                'description' => $case->description(),
            ])->all(),
            'clients' => Client::query()->orderBy('name')->get(['id', 'name']),
            'staff' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', OfficeAction::class);

        return Inertia::render('OfficeActions/Create', [
            'clients' => Client::query()->orderBy('name')->get(['id', 'name']),
            'staff' => User::query()->orderBy('name')->get(['id', 'name']),
            'types' => OfficeActionType::options(),
            'canAssign' => $this->actor($request)->isSupervisor(),
        ]);
    }

    public function store(StoreOfficeActionRequest $request, ActivityLogger $activities): RedirectResponse
    {
        $details = OfficeActionDetails::from($request->validated());
        $actor = $this->actor($request);

        $officeAction = DB::transaction(function () use ($details, $actor, $activities): OfficeAction {
            $clientId = $details->clientId;

            if ($clientId === null) {
                $client = Client::query()->create([
                    'organization_id' => $actor->organization_id,
                    'name' => $details->newClientName ?? 'Client',
                ]);
                $clientId = $client->id;
            }

            $deadline = $details->originalDeadline ?? OfficeAction::calculateDeadline(
                $details->mailingDate,
                $details->responsePeriodMonths,
            );

            $officeAction = OfficeAction::query()->create([
                'organization_id' => $actor->organization_id,
                'client_id' => $clientId,
                'client_reference' => $details->clientReference,
                'application_number' => $details->applicationNumber,
                'application_name' => $details->applicationName,
                'hci_reference' => $details->hciReference,
                'applicant_names' => $details->applicantNames,
                'paper_number' => $details->paperNumber,
                'type' => $details->type,
                'mailing_date' => $details->mailingDate,
                'response_period_months' => $details->responsePeriodMonths,
                'original_deadline' => $deadline,
                'internal_response_date' => $details->internalResponseDate,
                'reminder_date' => $details->reminderDate,
                'assignee_id' => $details->assigneeId,
                'assigned_by_id' => $details->assigneeId !== null ? $actor->id : null,
                'logged_by_id' => $actor->id,
            ]);

            $activities->created($officeAction, $actor, $officeAction->client_id, [
                'client_id',
                'client_reference',
                'application_number',
                'application_name',
                'hci_reference',
                'applicant_names',
                'paper_number',
                'type',
                'mailing_date',
                'response_period_months',
                'original_deadline',
                'internal_response_date',
                'reminder_date',
                'assignee_id',
            ]);

            return $officeAction;
        });

        return redirect()
            ->route('office-actions.show', $officeAction)
            ->with('success', 'Office action logged.');
    }

    public function show(
        Request $request,
        OfficeAction $officeAction,
        OfficeActionPresenter $presenter,
        EmailDraftRenderer $renderer,
    ): Response {
        Gate::authorize('view', $officeAction);

        $officeAction->load(['client.instructions.author', 'assignee']);

        $activities = Activity::query()
            ->with('user')
            ->forOfficeAction($officeAction)
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        $templates = EmailTemplate::query()->orderBy('id')->get();
        $selected = $request->string('draft')->toString();
        $firstTemplate = $templates->first();

        if (! $templates->contains('key', $selected)) {
            $selected = $firstTemplate instanceof EmailTemplate ? $firstTemplate->key : '';
        }

        $user = $request->user();

        return Inertia::render('OfficeActions/Show', [
            'officeAction' => $presenter->detail($officeAction, $activities),
            'clients' => Client::query()->orderBy('name')->get(['id', 'name']),
            'staff' => User::query()->orderBy('name')->get(['id', 'name']),
            'types' => OfficeActionType::options(),
            'templates' => $templates->map(fn (EmailTemplate $template): array => $renderer->render($template, $officeAction))->all(),
            'selectedTemplate' => $selected,
            'permissions' => [
                'update' => $user instanceof User && $user->can('update', $officeAction),
                'assign' => $user instanceof User && $user->can('assign', $officeAction),
            ],
        ]);
    }

    public function update(UpdateOfficeActionRequest $request, OfficeAction $officeAction, ActivityLogger $activities): RedirectResponse
    {
        $details = OfficeActionDetails::from($request->validated());

        if ($details->clientId === null) {
            throw ValidationException::withMessages([
                'client_id' => 'Choose a client.',
            ]);
        }

        $deadline = $details->originalDeadline ?? OfficeAction::calculateDeadline(
            $details->mailingDate,
            $details->responsePeriodMonths,
        );

        $officeAction->fill([
            'client_id' => $details->clientId,
            'client_reference' => $details->clientReference,
            'application_number' => $details->applicationNumber,
            'application_name' => $details->applicationName,
            'hci_reference' => $details->hciReference,
            'applicant_names' => $details->applicantNames,
            'paper_number' => $details->paperNumber,
            'type' => $details->type,
            'mailing_date' => $details->mailingDate,
            'response_period_months' => $details->responsePeriodMonths,
            'original_deadline' => $deadline,
            'internal_response_date' => $details->internalResponseDate,
            'reminder_date' => $details->reminderDate,
        ]);

        $activities->updated($officeAction, $this->actor($request), $officeAction->client_id);

        return redirect()
            ->route('office-actions.show', $officeAction)
            ->with('success', 'Office action details saved.');
    }
}
