<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $organization = Organization::factory()->create([
            'name' => 'HECHA',
        ]);

        User::factory()->supervisor()->for($organization)->create([
            'name' => 'Supervisor',
            'email' => 'supervisor@hecha.test',
        ]);

        User::factory()->for($organization)->create([
            'name' => 'Staff Member',
            'email' => 'staff@hecha.test',
        ]);
    }
}
