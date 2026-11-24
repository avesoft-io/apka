<?php

namespace App\Policies;

use App\Models\OfficeAction;
use App\Models\User;

class OfficeActionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, OfficeAction $officeAction): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, OfficeAction $officeAction): bool
    {
        if ($user->isSupervisor()) {
            return true;
        }

        if ($officeAction->assignee_id === null) {
            return $officeAction->logged_by_id === $user->id;
        }

        return $officeAction->assignee_id === $user->id;
    }

    public function assign(User $user, OfficeAction $officeAction): bool
    {
        return $user->isSupervisor();
    }
}
