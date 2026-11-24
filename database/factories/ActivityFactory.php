<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Client;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'user_id' => User::factory(),
            'subject_type' => Client::class,
            'subject_id' => Client::factory(),
            'client_id' => Client::factory(),
            'event' => 'updated',
            'changes' => [
                'name' => ['before' => 'Previous name', 'after' => 'Current name'],
            ],
        ];
    }
}
