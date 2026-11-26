<?php

namespace Database\Factories;

use App\CheckingStatus;
use App\ClientApprovalStatus;
use App\ExtensionStatus;
use App\Models\Client;
use App\Models\OfficeAction;
use App\Models\User;
use App\OfficeActionOutcome;
use App\OfficeActionType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;

/**
 * @extends Factory<OfficeAction>
 */
class OfficeActionFactory extends Factory
{
    public function configure(): static
    {
        return $this->afterMaking(function (OfficeAction $officeAction): void {
            if (($officeAction->getAttributes()['organization_id'] ?? null) !== null) {
                return;
            }

            $client = Client::query()->withoutGlobalScopes()->find($officeAction->client_id);

            if ($client instanceof Client) {
                $officeAction->organization_id = $client->organization_id;
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $mailing = Date::parse(fake()->dateTimeBetween('-2 months', '-1 week'));

        return [
            'client_id' => Client::factory(),
            'client_reference' => fake()->optional()->bothify('CR-####'),
            'application_number' => fake()->numerify('PH/1/####/######'),
            'application_name' => fake()->optional()->words(3, true),
            'hci_reference' => fake()->bothify('HCI-####'),
            'applicant_names' => [fake()->name(), fake()->name()],
            'paper_number' => (string) fake()->numberBetween(1, 12),
            'type' => fake()->randomElement(OfficeActionType::cases()),
            'mailing_date' => $mailing->toDateString(),
            'response_period_months' => 3,
            'original_deadline' => $mailing->addMonths(3)->toDateString(),
            'internal_response_date' => null,
            'reminder_date' => null,
            'reminder_sent_on' => null,
            'reminder_sent_for' => null,
            'extended_deadline' => null,
            'extension_status' => ExtensionStatus::None,
            'extension_requested_on' => null,
            'assignee_id' => null,
            'assigned_by_id' => null,
            'logged_by_id' => User::factory(),
            'outcome' => OfficeActionOutcome::Open,
            'abandoned_at' => null,
            'abandon_reason' => null,
            'response_instructions' => null,
            'instructions_received_on' => null,
            'report_sent_on' => null,
            'report_includes_fees' => false,
            'report_requests_instructions' => false,
            'draft_notes' => null,
            'response_prepared' => false,
            'amended_claims_prepared' => false,
            'other_documents_prepared' => false,
            'draft_prepared_on' => null,
            'checking_status' => CheckingStatus::None,
            'client_approval_required' => false,
            'client_approval_status' => ClientApprovalStatus::NotRequired,
            'filing_date' => null,
            'filing_reported_on' => null,
        ];
    }

    public function assignedTo(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'organization_id' => $user->organization_id,
            'client_id' => Client::factory()->state([
                'organization_id' => $user->organization_id,
            ]),
            'assignee_id' => $user->id,
            'assigned_by_id' => $user->id,
            'logged_by_id' => $user->id,
        ]);
    }

    public function reported(): static
    {
        return $this->state(fn (array $attributes) => [
            'report_sent_on' => now()->toDateString(),
            'report_requests_instructions' => true,
        ]);
    }

    public function instructed(string $instructions = 'Please prepare a response.'): static
    {
        return $this->state(fn (array $attributes) => [
            'response_instructions' => $instructions,
            'instructions_received_on' => now()->toDateString(),
        ]);
    }

    public function drafted(): static
    {
        return $this->state(fn (array $attributes) => [
            'response_prepared' => true,
            'amended_claims_prepared' => true,
            'draft_prepared_on' => now()->toDateString(),
            'draft_notes' => 'Response and amended claims prepared.',
        ]);
    }

    public function checked(): static
    {
        return $this->state(fn (array $attributes) => [
            'checking_status' => CheckingStatus::Checked,
        ]);
    }

    public function filed(): static
    {
        return $this->state(fn (array $attributes) => [
            'filing_date' => now()->toDateString(),
        ]);
    }
}
