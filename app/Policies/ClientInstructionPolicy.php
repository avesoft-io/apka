<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\ClientInstruction;
use App\Models\User;

class ClientInstructionPolicy
{
    public function create(User $user, Client $client): bool
    {
        return true;
    }

    public function delete(User $user, ClientInstruction $instruction): bool
    {
        return true;
    }
}
