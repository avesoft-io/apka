<?php

use App\CheckingStatus;
use App\ExtensionStatus;
use App\Models\Activity;
use App\Models\Client;
use App\Models\EmailTemplate;
use App\Models\OfficeAction;
use App\Models\Organization;
use App\Models\User;
use App\OfficeActionOutcome;
use App\OfficeActionType;
use Inertia\Testing\AssertableInertia as Assert;

test('stores comma-separated applicant names and rejects the wrong format', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $client = Client::factory()->for($organization)->create();

    $this->actingAs($user)->post(route('office-actions.store'), officeActionPayload($client, [
        'applicant_names' => '',
    ]))->assertSessionHasErrors([
        'applicant_names' => 'Enter the applicant name or names.',
    ]);

    foreach (['Ada Lovelace; Alan Turing', "Ada Lovelace\nAlan Turing", 'Ada Lovelace,', ',Ada Lovelace', 'Ada,, Lovelace'] as $names) {
        $this->post(route('office-actions.store'), officeActionPayload($client, [
            'applicant_names' => $names,
        ]))->assertSessionHasErrors([
            'applicant_names' => 'Separate each applicant name with a comma.',
        ]);
    }

    expect(OfficeAction::query()->count())->toBe(0);

    $this->post(route('office-actions.store'), officeActionPayload($client, [
        'applicant_names' => 'Ada Lovelace',
    ]))->assertRedirect();

    expect(OfficeAction::query()->firstOrFail()->applicant_names)->toBe(['Ada Lovelace']);

    $this->post(route('office-actions.store'), officeActionPayload($client, [
        'paper_number' => '4',
        'applicant_names' => 'Ada Lovelace, Alan Turing',
    ]))->assertRedirect();

    $officeAction = OfficeAction::query()->where('paper_number', '4')->firstOrFail();

    expect($officeAction->applicant_names)->toBe(['Ada Lovelace', 'Alan Turing']);
    expect(json_decode((string) $officeAction->getRawOriginal('applicant_names'), true))->toBe([
        'Ada Lovelace',
        'Alan Turing',
    ]);

    $this->get(route('office-actions.show', $officeAction))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where(
            'officeAction.applicant_names',
            'Ada Lovelace, Alan Turing',
        ));
});

test('requires an application number or an HCI reference', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $client = Client::factory()->for($organization)->create();

    $this->actingAs($user)->post(route('office-actions.store'), [
        'client_id' => $client->id,
        'paper_number' => '1',
        'type' => OfficeActionType::Ser->value,
        'mailing_date' => '2026-01-05',
        'response_period_months' => 2,
    ])->assertSessionHasErrors([
        'application_number' => 'Enter an application number or an HCI reference.',
    ]);

    expect(OfficeAction::query()->count())->toBe(0);
});

test('keeps assignment on the supervisor and shows the office action in that persons list', function () {
    $organization = Organization::factory()->create();
    $supervisor = User::factory()->supervisor()->for($organization)->create();
    $staff = User::factory()->for($organization)->create();
    $other = User::factory()->for($organization)->create();
    $client = Client::factory()->for($organization)->create();

    $this->actingAs($staff)->post(route('office-actions.store'), officeActionPayload($client, [
        'assignee_id' => $other->id,
    ]))->assertSessionHasErrors('assignee_id');

    $this->actingAs($supervisor)->post(route('office-actions.store'), officeActionPayload($client, [
        'assignee_id' => $staff->id,
    ]))->assertRedirect();

    $officeAction = OfficeAction::query()->firstOrFail();

    expect($officeAction->original_deadline->toDateString())->toBe('2026-03-05');
    expect($officeAction->assignee_id)->toBe($staff->id);

    $this->actingAs($staff)
        ->get(route('office-actions.index', ['list' => 'mine']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('officeActions.data', 1));

    $this->actingAs($other)
        ->get(route('office-actions.index', ['list' => 'mine']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('officeActions.data', 0));

    $this->actingAs($staff)
        ->patch(route('office-actions.assignment.update', $officeAction), [
            'assignee_id' => $other->id,
        ])->assertForbidden();
});

test('records who changed an assignment and what it was before', function () {
    $organization = Organization::factory()->create();
    $supervisor = User::factory()->supervisor()->for($organization)->create(['name' => 'Office Supervisor']);
    $first = User::factory()->for($organization)->create(['name' => 'First Attorney']);
    $second = User::factory()->for($organization)->create(['name' => 'Second Attorney']);
    $officeAction = OfficeAction::factory()->create([
        'organization_id' => $organization->id,
        'client_id' => Client::factory()->for($organization),
        'logged_by_id' => $supervisor->id,
        'assignee_id' => null,
    ]);

    $this->actingAs($supervisor)
        ->patch(route('office-actions.assignment.update', $officeAction), [
            'assignee_id' => $first->id,
        ])->assertRedirect();

    $this->actingAs($supervisor)
        ->patch(route('office-actions.assignment.update', $officeAction), [
            'assignee_id' => $second->id,
        ])->assertRedirect();

    $activity = Activity::query()->where('event', 'updated')->latest('id')->first();

    expect($activity)->not->toBeNull();
    expect($activity->user_id)->toBe($supervisor->id);
    expect($activity->changes['assignee_id']['before'])->toEqual($first->id);
    expect($activity->changes['assignee_id']['after'])->toEqual($second->id);

    $this->actingAs($supervisor)
        ->get(route('office-actions.show', $officeAction))
        ->assertInertia(fn (Assert $page) => $page->where('officeAction.history', function ($history) use ($first, $second) {
            $assignee = collect($history->first()['entries'] ?? [])->firstWhere('field', 'Assignee');

            return ($history->first()['user'] ?? null) === 'Office Supervisor'
                && ($assignee['before'] ?? null) === $first->name
                && ($assignee['after'] ?? null) === $second->name;
        }));
});

test('follows the office action checklist through filing and completion', function () {
    $staff = User::factory()->create();
    $officeAction = OfficeAction::factory()->assignedTo($staff)->create([
        'mailing_date' => '2026-01-05',
        'original_deadline' => '2026-03-05',
    ]);

    $this->actingAs($staff)
        ->get(route('office-actions.show', $officeAction))
        ->assertInertia(fn (Assert $page) => $page->where('officeAction.checklist', function ($steps) {
            return $steps->pluck('key')->doesntContain('extension')
                && $steps->pluck('key')->doesntContain('client_approval');
        }));

    $this->actingAs($staff)->patch(route('office-actions.report.update', $officeAction), [
        'report_sent_on' => '2026-01-06',
        'report_includes_fees' => true,
        'report_requests_instructions' => true,
    ])->assertRedirect();

    $this->actingAs($staff)->patch(route('office-actions.instructions.update', $officeAction), [
        'response_instructions' => 'Prepare a response and amend claim 1.',
        'instructions_received_on' => '2026-01-10',
    ])->assertRedirect();

    $this->actingAs($staff)->patch(route('office-actions.draft.update', $officeAction), [
        'response_prepared' => true,
        'amended_claims_prepared' => true,
        'other_documents_prepared' => false,
        'draft_notes' => 'Response and amended claim 1.',
        'draft_prepared_on' => '2026-01-20',
    ])->assertRedirect();

    $this->actingAs($staff)->patch(route('office-actions.checking.update', $officeAction), [
        'checking_status' => CheckingStatus::ForChecking->value,
    ])->assertRedirect();

    $this->get(route('office-actions.index', ['list' => 'for-checking']))
        ->assertInertia(fn (Assert $page) => $page->has('officeActions.data', 1));

    $this->patch(route('office-actions.checking.update', $officeAction), [
        'checking_status' => CheckingStatus::Checked->value,
    ])->assertRedirect();

    $this->patch(route('office-actions.filing.update', $officeAction), [
        'filing_date' => '2026-02-01',
    ])->assertRedirect();

    $this->get(route('office-actions.index', ['list' => 'pending-reporting']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('officeActions.data', 1)
            ->where('officeActions.data.0.status', 'Filed, pending reporting'));

    $this->patch(route('office-actions.filing-report.update', $officeAction), [
        'filing_reported_on' => '2026-02-02',
    ])->assertRedirect();

    expect($officeAction->refresh()->outcome)->toBe(OfficeActionOutcome::Completed);

    $this->get(route('office-actions.index', ['list' => 'pending-reporting']))
        ->assertInertia(fn (Assert $page) => $page->has('officeActions.data', 0));
});

test('keeps the original deadline when an extension is confirmed', function () {
    $staff = User::factory()->create();
    $officeAction = OfficeAction::factory()
        ->assignedTo($staff)
        ->reported()
        ->instructed()
        ->drafted()
        ->checked()
        ->create([
            'original_deadline' => '2026-03-05',
        ]);

    $this->actingAs($staff)->post(route('office-actions.extension.store', $officeAction), [
        'extension_requested_on' => '2026-02-01',
    ])->assertRedirect();

    $this->patch(route('office-actions.filing.update', $officeAction), [
        'filing_date' => '2026-02-15',
    ])->assertSessionHasErrors('filing_date');

    $this->patch(route('office-actions.extension.confirm', $officeAction), [
        'extended_deadline' => '2026-06-05',
    ])->assertRedirect();

    $officeAction->refresh();

    expect($officeAction->extension_status)->toBe(ExtensionStatus::Confirmed);
    expect($officeAction->original_deadline->toDateString())->toBe('2026-03-05');
    expect($officeAction->extended_deadline->toDateString())->toBe('2026-06-05');

    $this->get(route('office-actions.show', $officeAction))
        ->assertInertia(fn (Assert $page) => $page
            ->where('officeAction.original_deadline', '2026-03-05')
            ->where('officeAction.extended_deadline', '2026-06-05')
            ->where('officeAction.current_deadline', '2026-06-05')
            ->where('officeAction.extension_status', 'confirmed'));
});

test('blocks filing until required client approval is recorded', function () {
    $staff = User::factory()->create();
    $officeAction = OfficeAction::factory()
        ->assignedTo($staff)
        ->reported()
        ->instructed()
        ->drafted()
        ->checked()
        ->create();

    $this->actingAs($staff)->patch(route('office-actions.approval.update', $officeAction), [
        'client_approval_required' => true,
        'approved' => false,
    ])->assertRedirect();

    $this->patch(route('office-actions.filing.update', $officeAction), [
        'filing_date' => '2026-02-15',
    ])->assertSessionHasErrors('filing_date');

    $this->get(route('office-actions.index', ['list' => 'ready-to-file']))
        ->assertInertia(fn (Assert $page) => $page->has('officeActions.data', 0));

    $this->patch(route('office-actions.approval.update', $officeAction), [
        'client_approval_required' => true,
        'approved' => true,
    ])->assertRedirect();

    $this->get(route('office-actions.index', ['list' => 'ready-to-file']))
        ->assertInertia(fn (Assert $page) => $page->has('officeActions.data', 1));
});

test('closes an office action when the client instructs abandonment', function () {
    $staff = User::factory()->create();
    $officeAction = OfficeAction::factory()->assignedTo($staff)->reported()->create();

    $this->actingAs($staff)->post(route('office-actions.abandonment.store', $officeAction), [
        'instruction' => 'The client told us to abandon the application.',
        'reason' => 'The applicant will not pursue the application.',
    ])->assertRedirect();

    $officeAction->refresh();

    expect($officeAction->outcome)->toBe(OfficeActionOutcome::Abandoned);
    expect($officeAction->abandon_reason)->toBe('The applicant will not pursue the application.');
    expect($officeAction->response_instructions)->toBe('The client told us to abandon the application.');

    $this->get(route('office-actions.index', ['list' => 'waiting-instructions']))
        ->assertInertia(fn (Assert $page) => $page->has('officeActions.data', 0));

    $this->get(route('office-actions.show', $officeAction))
        ->assertInertia(fn (Assert $page) => $page
            ->where('officeAction.status', 'Abandoned')
            ->where('officeAction.abandon_reason', 'The applicant will not pursue the application.')
            ->where('officeAction.checklist', function ($steps) {
                return $steps->contains(fn ($step) => ($step['state'] ?? null) === 'skipped');
            }));
});

test('shows a due reminder until it is marked sent', function () {
    $staff = User::factory()->create();
    OfficeAction::factory()->assignedTo($staff)->create([
        'reminder_date' => now()->toDateString(),
    ]);

    $this->actingAs($staff)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->has('reminders', 1));

    $officeAction = OfficeAction::query()->firstOrFail();

    $this->patch(route('office-actions.reminder.update', $officeAction))->assertRedirect();

    $this->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->has('reminders', 0));

    $this->get(route('office-actions.index', ['list' => 'follow-ups']))
        ->assertInertia(fn (Assert $page) => $page->has('officeActions.data', 0));
});

test('fills a reminder draft from the office action and lists standing instructions', function () {
    $staff = User::factory()->create();
    $client = Client::factory()->for($staff->organization)->create();
    $officeAction = OfficeAction::factory()->assignedTo($staff)->create([
        'client_id' => $client->id,
        'organization_id' => $staff->organization_id,
        'application_number' => 'PH/1/2024/000123',
    ]);

    $this->actingAs($staff)->post(route('clients.instructions.store', $client), [
        'body' => 'Always copy the client on extension requests.',
    ])->assertRedirect();

    $this->get(route('office-actions.show', ['officeAction' => $officeAction, 'draft' => 'reminder']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('selectedTemplate', 'reminder')
            ->where('officeAction.standing_instructions.0.body', 'Always copy the client on extension requests.')
            ->where('templates', function ($templates) {
                $reminder = $templates->firstWhere('key', 'reminder');

                return is_array($reminder) && str_contains((string) $reminder['body'], 'PH/1/2024/000123');
            }));
});

test('filters lists by client and exports them to a spreadsheet', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $included = Client::factory()->for($organization)->create(['name' => 'Included Client']);
    $excluded = Client::factory()->for($organization)->create(['name' => 'Excluded Client']);
    $officeAction = OfficeAction::factory()->create([
        'organization_id' => $organization->id,
        'client_id' => $included->id,
        'logged_by_id' => $user->id,
        'application_number' => 'PH/1/2024/000777',
    ]);
    OfficeAction::factory()->create([
        'organization_id' => $organization->id,
        'client_id' => $excluded->id,
        'logged_by_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('office-actions.index', ['list' => 'all', 'client_id' => $included->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('officeActions.data', 1)
            ->where('officeActions.data.0.id', $officeAction->id));

    $response = $this->get(route('office-actions.export', [
        'list' => 'all',
        'client_id' => $included->id,
    ]));

    $response->assertOk();
    $response->assertDownload();

    $zip = new ZipArchive;
    $zip->open($response->baseResponse->getFile()->getPathname());
    $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();

    expect($sheet)->toBeString()->toContain('PH/1/2024/000777')->toContain('Included Client');
    expect($sheet)->not->toContain('Excluded Client');
});

test('forbids a staff member from working another persons office action', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->for($organization)->create();
    $other = User::factory()->for($organization)->create();
    $officeAction = OfficeAction::factory()->assignedTo($owner)->create();

    $this->actingAs($other)
        ->patch(route('office-actions.report.update', $officeAction), [
            'report_sent_on' => '2026-01-06',
            'report_includes_fees' => false,
            'report_requests_instructions' => true,
        ])->assertForbidden();
});

test('adds a user to the supervisor firm and keeps that user out of other firms', function () {
    $firm = Organization::factory()->create(['name' => 'North Office']);
    $other = Organization::factory()->create(['name' => 'South Office']);
    $supervisor = User::factory()->supervisor()->for($firm)->create();
    $client = Client::factory()->for($other)->create(['name' => 'South Client']);
    $officeAction = OfficeAction::factory()->create([
        'organization_id' => $other->id,
        'client_id' => $client->id,
    ]);

    $this->actingAs($supervisor)->post(route('staff.store'), [
        'name' => 'New Attorney',
        'email' => 'new.attorney@north.test',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'staff',
        'organization_id' => $other->id,
    ])->assertRedirect(route('staff.index'));

    $user = User::query()->where('email', 'new.attorney@north.test')->firstOrFail();

    expect($user->organization_id)->toBe($firm->id)
        ->and($user->hasVerifiedEmail())->toBeFalse();

    $user->markEmailAsVerified();

    $this->actingAs($user)
        ->get(route('office-actions.show', $officeAction))
        ->assertNotFound();

    $this->actingAs($user)
        ->get(route('clients.index'))
        ->assertInertia(fn (Assert $page) => $page->has('clients', 0));
});

test('lets a supervisor add staff and keeps template edits to supervisors', function () {
    $organization = Organization::factory()->create();
    $supervisor = User::factory()->supervisor()->for($organization)->create();
    $staff = User::factory()->for($organization)->create();

    $this->actingAs($staff)->get(route('staff.index'))->assertForbidden();

    $this->actingAs($supervisor)->post(route('staff.store'), [
        'name' => 'New Attorney',
        'email' => 'new.attorney@hecha.test',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'staff',
    ])->assertRedirect();

    expect(User::query()->where('email', 'new.attorney@hecha.test')->exists())->toBeTrue();

    $template = EmailTemplate::query()->where('key', 'reminder')->firstOrFail();

    $this->actingAs($staff)->patch(route('email-templates.update', $template), [
        'subject' => 'Changed',
        'body' => 'Changed body',
    ])->assertForbidden();
});

test('saves a client for the firm and removes a standing instruction', function () {
    $organization = Organization::factory()->create();
    $other = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $outsider = User::factory()->for($other)->create();

    $this->actingAs($user)->post(route('clients.store'), [])
        ->assertSessionHasErrors('name');

    $this->post(route('clients.store'), [
        'name' => 'Northwind Patents',
        'code' => 'NWP',
    ])->assertRedirect();

    $client = Client::query()->where('name', 'Northwind Patents')->firstOrFail();

    expect($client->organization_id)->toBe($organization->id);
    expect($client->code)->toBe('NWP');

    $this->actingAs($outsider)->get(route('clients.show', $client))->assertNotFound();

    $this->actingAs($user)->patch(route('clients.update', $client), [
        'name' => 'Northwind IP',
        'code' => 'NWP',
    ])->assertRedirect(route('clients.show', $client));

    $this->post(route('clients.instructions.store', $client), [
        'body' => 'Copy the inventor.',
    ])->assertRedirect(route('clients.show', $client));

    $instruction = $client->instructions()->firstOrFail();

    $this->delete(route('clients.instructions.destroy', [$client, $instruction]))
        ->assertRedirect(route('clients.show', $client));

    expect($client->refresh()->name)->toBe('Northwind IP');
    expect($client->instructions()->count())->toBe(0);
});

test('withdraws a pending extension and allows filing again', function () {
    $staff = User::factory()->create();
    $officeAction = OfficeAction::factory()
        ->assignedTo($staff)
        ->reported()
        ->instructed()
        ->drafted()
        ->checked()
        ->create([
            'original_deadline' => '2026-03-05',
        ]);

    $this->actingAs($staff)->post(route('office-actions.extension.store', $officeAction), [
        'extension_requested_on' => '2026-02-01',
    ])->assertRedirect();

    $this->delete(route('office-actions.extension.destroy', $officeAction))->assertRedirect();

    expect($officeAction->refresh()->extension_status)->toBe(ExtensionStatus::None);

    $this->patch(route('office-actions.filing.update', $officeAction), [
        'filing_date' => '2026-02-15',
    ])->assertRedirect();

    expect($officeAction->refresh()->filing_date->toDateString())->toBe('2026-02-15');
});

test('saves office action details and a supervisor email template', function () {
    $organization = Organization::factory()->create();
    $supervisor = User::factory()->supervisor()->for($organization)->create();
    $client = Client::factory()->for($organization)->create();
    $officeAction = OfficeAction::factory()->assignedTo($supervisor)->create([
        'organization_id' => $organization->id,
        'client_id' => $client->id,
    ]);

    $this->actingAs($supervisor)->patch(route('office-actions.update', $officeAction), officeActionPayload($client, [
        'paper_number' => '9',
    ]))->assertRedirect(route('office-actions.show', $officeAction));

    expect($officeAction->refresh()->paper_number)->toBe('9');

    $template = EmailTemplate::query()->where('key', 'reminder')->firstOrFail();

    $this->patch(route('email-templates.update', $template), [
        'subject' => 'Reminder for {{reference}}',
        'body' => 'Please reply.',
    ])->assertRedirect(route('email-templates.index'));

    expect($template->refresh()->subject)->toBe('Reminder for {{reference}}');
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function officeActionPayload(Client $client, array $overrides = []): array
{
    return [
        'client_id' => $client->id,
        'application_number' => 'PH/1/2024/000123',
        'hci_reference' => 'HCI-1001',
        'applicant_names' => 'Ada Lovelace, Alan Turing',
        'paper_number' => '3',
        'type' => OfficeActionType::Ser->value,
        'mailing_date' => '2026-01-05',
        'response_period_months' => 2,
        ...$overrides,
    ];
}
