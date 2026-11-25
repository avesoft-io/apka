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
        'password' => 'password1',
        'password_confirmation' => 'password1',
    ])->assertRedirect(route('verification.notice'));

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
        'password' => 'password1',
        'password_confirmation' => 'password1',
    ])->assertRedirect(route('verification.notice'));

    $ben = User::query()->where('email', 'ben@south.test')->firstOrFail();
    $ben->markEmailAsVerified();
    $this->actingAs($ben);

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

test('rejects an email that fails the PHP email filter', function () {
    $this->post(route('register.store'), [
        'organization_name' => 'North Office',
        'name' => 'Ava Supervisor',
        'email' => 'not-an-email',
        'password' => 'password1',
        'password_confirmation' => 'password1',
    ])->assertSessionHasErrors('email');

    $this->postJson(route('register.email'), [
        'email' => 'not-an-email',
    ])->assertOk()->assertJson(['message' => 'Enter a valid email address.']);

    $this->postJson(route('register.email'), [
        'email' => 'ava@north.test',
    ])->assertOk()->assertJson(['message' => null]);

    expect(Organization::query()->where('name', 'North Office')->exists())->toBeFalse();
});

test('rejects a password that is missing a letter or a number', function () {
    $this->post(route('register.store'), [
        'organization_name' => 'North Office',
        'name' => 'Ava Supervisor',
        'email' => 'ava@north.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('password');

    $this->post(route('register.store'), [
        'organization_name' => 'North Office',
        'name' => 'Ava Supervisor',
        'email' => 'ava@north.test',
        'password' => '12345678',
        'password_confirmation' => '12345678',
    ])->assertSessionHasErrors('password');

    expect(Organization::query()->where('name', 'North Office')->exists())->toBeFalse();
});
