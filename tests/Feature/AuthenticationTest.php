<?php

use App\Models\User;

test('shows login and registration before anyone has registered', function () {
    $this->get(route('login'))->assertOk();
    $this->get(route('register'))->assertOk();
});

test('signs in a staff member and sends guests to the login page', function () {
    $user = User::factory()->create([
        'email' => 'staff@hecha.test',
    ]);

    $this->get(route('dashboard'))->assertRedirect(route('login'));

    $this->post(route('login.store'), [
        'email' => 'not-a-person@hecha.test',
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->post(route('login.store'), [
        'email' => 'staff@hecha.test',
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('checks the login email with the PHP filter and ignores unsafe characters', function () {
    $user = User::factory()->create([
        'email' => 'staff@hecha.test',
    ]);

    $this->post(route('login.store'), [
        'email' => 'not-an-email',
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->postJson(route('login.email'), [
        'email' => 'not-an-email',
    ])->assertOk()->assertJson(['message' => 'Enter a valid email address.']);

    $this->postJson(route('login.email'), [
        'email' => 'staff@hecha.test',
    ])->assertOk()->assertJson(['message' => null]);

    $this->post(route('login.store'), [
        'email' => "staff@hecha.test\0",
        'password' => "pass\0word",
        'remember' => "1'); DROP TABLE users;--",
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
    expect(User::query()->count())->toBe(1);
});
