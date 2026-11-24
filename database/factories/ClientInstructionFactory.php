<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\ClientInstruction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientInstruction>
 */
class ClientInstructionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'created_by_id' => User::factory(),
            'body' => fake()->sentence(),
        ];
    }
}
