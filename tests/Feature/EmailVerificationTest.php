<?php

use App\Models\User;
use App\Notifications\VerifyFirmInvitation;
use App\Notifications\VerifyRegistration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

test('a new firm must verify its email within five minutes', function () {
    Notification::fake();

    $this->post(route('register.store'), [
        'organization_name' => 'North Office',
        'name' => 'Ava Supervisor',
        'email' => 'ava@north.test',
        'password' => 'password1',
        'password_confirmation' => 'password1',
    ])->assertRedirect(route('verification.notice'));

    $user = User::query()->where('email', 'ava@north.test')->firstOrFail();

    expect($user->hasVerifiedEmail())->toBeFalse()
        ->and($user->invited_by_id)->toBeNull();

    Notification::assertSentTo($user, VerifyRegistration::class, function (VerifyRegistration $notification) use ($user): bool {
        $mail = $notification->toMail($user);
        $lines = implode(' ', $mail->introLines);

        return $mail->subject === 'Verify your North Office firm'
            && str_contains($lines, '5 minutes')
            && str_contains($lines, 'You registered North Office')
            && ! str_contains($lines, 'added you')
            && ! str_contains($lines, 'password');
    });

    $this->get(route('dashboard'))->assertRedirect(route('verification.notice'));
    $this->get(route('verification.notice'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Auth/VerifyEmail'));

    $this->get($user->emailVerificationUrl())->assertRedirect(route('dashboard'));

    expect($user->fresh()?->hasVerifiedEmail())->toBeTrue();
    $this->assertAuthenticatedAs($user);
    $this->get(route('dashboard'))->assertOk();
});

test('an expired verification link is rejected', function () {
    $user = User::factory()->unverified()->create();
    $url = $user->emailVerificationUrl();

    $this->travel(6)->minutes();

    $this->get($url)
        ->assertForbidden()
        ->assertInertia(fn ($page) => $page->component('Auth/VerifyEmailExpired'));

    expect($user->fresh()?->hasVerifiedEmail())->toBeFalse();
});

test('an unverified user can request another verification email', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect()
        ->assertSessionHas('success', 'A new verification link was sent.');

    Notification::assertSentTo($user, VerifyRegistration::class);

    $inviter = User::factory()->supervisor()->for($user->organization)->create();
    $user->forceFill(['invited_by_id' => $inviter->id])->save();

    $this->actingAs($user->fresh() ?? $user)
        ->post(route('verification.send'))
        ->assertRedirect();

    Notification::assertSentTo($user, VerifyFirmInvitation::class);
});

test('an unverified user is sent to the verification page after signing in', function () {
    User::factory()->unverified()->create([
        'email' => 'ava@north.test',
    ]);

    $this->post(route('login.store'), [
        'email' => 'ava@north.test',
        'password' => 'password',
    ])->assertRedirect(route('verification.notice'));

    $this->get(route('office-actions.index'))->assertRedirect(route('verification.notice'));
});

test('a supervisor invitation uses a different email', function () {
    Notification::fake();

    $supervisor = User::factory()->supervisor()->create();

    $this->actingAs($supervisor)->post(route('staff.store'), [
        'name' => 'Casey Rivera',
        'email' => 'casey@hecha.test',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'staff',
    ])->assertRedirect(route('staff.index'));

    $user = User::query()->where('email', 'casey@hecha.test')->firstOrFail();

    expect($user->invited_by_id)->toBe($supervisor->id)
        ->and($user->hasVerifiedEmail())->toBeFalse()
        ->and($user->organization_id)->toBe($supervisor->organization_id);

    Notification::assertNotSentTo($user, VerifyRegistration::class);
    Notification::assertSentTo($user, VerifyFirmInvitation::class, function (VerifyFirmInvitation $notification) use ($user, $supervisor): bool {
        $mail = $notification->toMail($user);
        $lines = implode(' ', $mail->introLines);

        return $mail->subject === 'You have been added to '.$supervisor->organization->name
            && str_contains($lines, $supervisor->name.' added you')
            && str_contains($lines, '5 minutes')
            && str_contains($lines, 'password they gave you')
            && ! str_contains($lines, 'You registered');
    });

    $this->actingAs($supervisor)
        ->get(route('staff.index'))
        ->assertInertia(fn ($page) => $page
            ->where('staff', function (Collection $staff) use ($user): bool {
                $person = $staff->firstWhere('email', $user->email);

                return is_array($person) && $person['verified'] === false;
            }));
});
