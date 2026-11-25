<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRegistrationRequest;
use App\Models\Organization;
use App\Models\User;
use App\UserRole;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(StoreRegistrationRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request): User {
            $organization = Organization::query()->create([
                'name' => $request->string('organization_name')->toString(),
            ]);

            (new EmailTemplateSeeder)->seed($organization);

            return User::query()->create([
                'organization_id' => $organization->id,
                'name' => $request->string('name')->toString(),
                'email' => $request->string('email')->toString(),
                'password' => $request->string('password')->toString(),
                'role' => UserRole::Supervisor,
            ]);
        });

        Auth::login($user);
        $request->session()->regenerate();
        $user->sendEmailVerificationNotification();

        return redirect()->route('verification.notice');
    }
}
