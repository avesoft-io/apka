<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStaffRequest;
use App\Models\User;
use App\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', User::class);

        return Inertia::render('Staff/Index', [
            'staff' => User::query()
                ->orderBy('name')
                ->get()
                ->map(fn (User $user): array => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                    'verified' => $user->hasVerifiedEmail(),
                ])
                ->all(),
            'roles' => UserRole::options(),
        ]);
    }

    public function store(StoreStaffRequest $request): RedirectResponse
    {
        $actor = $this->actor($request);

        $user = User::query()->create([
            'organization_id' => $actor->organization_id,
            'invited_by_id' => $actor->id,
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
            'role' => UserRole::from($request->string('role')->toString()),
        ]);

        $user->sendEmailVerificationNotification();

        return redirect()
            ->route('staff.index')
            ->with('success', 'User added. We emailed them a verification link that expires in 5 minutes.');
    }
}
