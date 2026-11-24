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
