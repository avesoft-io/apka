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
            'staff' => User::query()->orderBy('name')->get(['id', 'name', 'email', 'role']),
            'roles' => UserRole::options(),
        ]);
    }

    public function store(StoreStaffRequest $request): RedirectResponse
    {
        User::query()->create([
            'organization_id' => $this->actor($request)->organization_id,
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
            'role' => UserRole::from($request->string('role')->toString()),
        ]);

        return redirect()
            ->route('staff.index')
            ->with('success', 'User added to your firm.');
    }
}
