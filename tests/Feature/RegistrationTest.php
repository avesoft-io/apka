<?php

use App\Models\EmailTemplate;
use App\Models\OfficeAction;
use App\Models\Organization;
use App\Models\User;
use App\UserRole;

test('registers a firm and keeps its records private', function () {
    $this->post(route('register.store'), [
        'organization_name' => 'North Office',
        'name' => 'Ava Supervisor',
        'email' => 'ava@north.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();

    $user = User::query()->where('email', 'ava@north.test')->firstOrFail();

    expect($user->role)->toBe(UserRole::Supervisor)
        ->and($user->organization->name)->toBe('North Office');

    expect(EmailTemplate::query()->where('key', 'reminder')->count())->toBe(1);

    $officeAction = OfficeAction::factory()->assignedTo($user)->create();

    $this->post(route('logout'))->assertRedirect(route('login'));

    $this->post(route('register.store'), [
        'organization_name' => 'South Office',
        'name' => 'Ben Supervisor',
        'email' => 'ben@south.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard'));

    $this->get(route('office-actions.show', $officeAction))->assertNotFound();

    $this->get(route('office-actions.index', ['list' => 'all']))
        ->assertInertia(fn ($page) => $page->has('officeActions.data', 0));

    $this->get(route('clients.index'))
        ->assertInertia(fn ($page) => $page->has('clients', 0));
});

test('rejects a duplicate email and a short password', function () {
    $organization = Organization::factory()->create();
    User::factory()->for($organization)->create([
        'email' => 'ava@north.test',
    ]);

    $this->post(route('register.store'), [
        'organization_name' => 'North Office',
        'name' => 'Ava Supervisor',
        'email' => 'ava@north.test',
        'password' => 'short',
        'password_confirmation' => 'short',
    ])->assertSessionHasErrors(['email', 'password']);

    expect(Organization::query()->where('name', 'North Office')->exists())->toBeFalse();
});
