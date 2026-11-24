<?php

namespace Database\Factories;

use App\Models\EmailTemplate;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailTemplate>
 */
class EmailTemplateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'key' => fake()->unique()->slug(2),
            'name' => fake()->words(3, true),
            'subject' => 'Update for {{reference}}',
            'body' => "Hello {{client}},\n\nThis concerns {{reference}}. The current deadline is {{current_deadline}}.",
        ];
    }
}
