<?php

namespace Database\Factories;

use App\Models\Organization;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Organization $organization): void {
            (new EmailTemplateSeeder)->seed($organization);
        });
    }
}
