<?php

namespace App\Models;

use App\CheckingStatus;
use App\ClientApprovalStatus;
use App\ExtensionStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\OfficeActionOutcome;
use App\OfficeActionType;
use Carbon\CarbonInterface;
use Database\Factories\OfficeActionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Date;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $client_id
 * @property string|null $application_number
 * @property string|null $hci_reference
 * @property list<string>|null $applicant_names
 * @property string $paper_number
 * @property OfficeActionType $type
 * @property CarbonInterface $mailing_date
 * @property int $response_period_months
 * @property CarbonInterface $original_deadline
 * @property CarbonInterface|null $internal_response_date
 * @property CarbonInterface|null $reminder_date
 * @property CarbonInterface|null $reminder_sent_on
 * @property CarbonInterface|null $reminder_sent_for
 * @property CarbonInterface|null $extended_deadline
 * @property ExtensionStatus $extension_status
 * @property CarbonInterface|null $extension_requested_on
 * @property int|null $assignee_id
 * @property int|null $assigned_by_id
 * @property int $logged_by_id
 * @property OfficeActionOutcome $outcome
 * @property CarbonInterface|null $abandoned_at
 * @property string|null $abandon_reason
 * @property string|null $response_instructions
 * @property CarbonInterface|null $instructions_received_on
 * @property CarbonInterface|null $report_sent_on
 * @property bool $report_includes_fees
 * @property bool $report_requests_instructions
 * @property string|null $draft_notes
 * @property bool $response_prepared
 * @property bool $amended_claims_prepared
 * @property bool $other_documents_prepared
 * @property CarbonInterface|null $draft_prepared_on
 * @property CheckingStatus $checking_status
 * @property bool $client_approval_required
 * @property ClientApprovalStatus $client_approval_status
 * @property CarbonInterface|null $filing_date
 * @property CarbonInterface|null $filing_reported_on
 * @property-read Client $client
 * @property-read User|null $assignee
 * @property-read User|null $assignedBy
 * @property-read User $logger
 */
#[Fillable([
    'organization_id',
    'client_id',
    'application_number',
    'hci_reference',
    'applicant_names',
    'paper_number',
    'type',
    'mailing_date',
    'response_period_months',
    'original_deadline',
    'internal_response_date',
    'reminder_date',
    'reminder_sent_on',
    'reminder_sent_for',
    'extended_deadline',
    'extension_status',
    'extension_requested_on',
    'assignee_id',
    'assigned_by_id',
    'logged_by_id',
    'outcome',
    'abandoned_at',
    'abandon_reason',
    'response_instructions',
    'instructions_received_on',
    'report_sent_on',
    'report_includes_fees',
    'report_requests_instructions',
    'draft_notes',
    'response_prepared',
    'amended_claims_prepared',
    'other_documents_prepared',
    'draft_prepared_on',
    'checking_status',
    'client_approval_required',
    'client_approval_status',
    'filing_date',
    'filing_reported_on',
])]
class OfficeAction extends Model
{
    /** @use HasFactory<OfficeActionFactory> */
    use BelongsToOrganization, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'applicant_names' => 'array',
            'type' => OfficeActionType::class,
            'mailing_date' => 'date',
            'response_period_months' => 'integer',
            'original_deadline' => 'date',
            'internal_response_date' => 'date',
            'reminder_date' => 'date',
            'reminder_sent_on' => 'date',
            'reminder_sent_for' => 'date',
            'extended_deadline' => 'date',
            'extension_status' => ExtensionStatus::class,
            'extension_requested_on' => 'date',
            'outcome' => OfficeActionOutcome::class,
            'abandoned_at' => 'datetime',
            'instructions_received_on' => 'date',
            'report_sent_on' => 'date',
            'report_includes_fees' => 'boolean',
            'report_requests_instructions' => 'boolean',
            'response_prepared' => 'boolean',
            'amended_claims_prepared' => 'boolean',
            'other_documents_prepared' => 'boolean',
            'draft_prepared_on' => 'date',
            'checking_status' => CheckingStatus::class,
            'client_approval_required' => 'boolean',
            'client_approval_status' => ClientApprovalStatus::class,
            'filing_date' => 'date',
            'filing_reported_on' => 'date',
        ];
    }

    public static function calculateDeadline(string $mailingDate, int $months): string
    {
        $mailing = Date::parse($mailingDate);

        return $mailing->addMonths($months)->toDateString();
    }

    public function currentDeadline(): CarbonInterface
    {
        if ($this->extension_status === ExtensionStatus::Confirmed && $this->extended_deadline !== null) {
            return $this->extended_deadline;
        }

        return $this->original_deadline;
    }

    public function referenceLabel(): string
    {
        $parts = array_values(array_filter([
            $this->application_number,
            $this->hci_reference !== null ? 'HCI '.$this->hci_reference : null,
        ]));

        if ($parts === []) {
            return 'Office action #'.$this->id;
        }

        return implode(' · ', $parts);
    }

    /**
     * @param  Builder<OfficeAction>  $query
     * @return Builder<OfficeAction>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('outcome', OfficeActionOutcome::Open);
    }

    /**
     * @param  Builder<OfficeAction>  $query
     * @return Builder<OfficeAction>
     */
    public function scopeAssignedTo(Builder $query, User $user): Builder
    {
        return $query->where('assignee_id', $user->id);
    }

    /**
     * @param  Builder<OfficeAction>  $query
     * @return Builder<OfficeAction>
     */
    public function scopeOrderByCurrentDeadline(Builder $query): Builder
    {
        return $query->orderByRaw(
            'case when extension_status = ? then extended_deadline else original_deadline end asc',
            [ExtensionStatus::Confirmed->value],
        )->orderBy('id');
    }

    /**
     * @param  Builder<OfficeAction>  $query
     * @return Builder<OfficeAction>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        $until = today()->addDays(30)->toDateString();

        return $query->open()
            ->whereNull('filing_date')
            ->where(function (Builder $inner) use ($until): void {
                $inner->where(function (Builder $extended) use ($until): void {
                    $extended->where('extension_status', ExtensionStatus::Confirmed)
                        ->whereDate('extended_deadline', '<=', $until);
                })->orWhere(function (Builder $original) use ($until): void {
                    $original->where('extension_status', '!=', ExtensionStatus::Confirmed)
                        ->whereDate('original_deadline', '<=', $until);
                });
            });
    }

    /**
     * @param  Builder<OfficeAction>  $query
     * @return Builder<OfficeAction>
     */
    public function scopeFollowUps(Builder $query): Builder
    {
        return $query->open()
            ->whereNotNull('reminder_date')
            ->whereDate('reminder_date', '<=', today())
            ->where(function (Builder $inner): void {
                $inner->whereNull('reminder_sent_for')
                    ->orWhereColumn('reminder_sent_for', '!=', 'reminder_date');
            });
    }

    /**
     * @param  Builder<OfficeAction>  $query
     * @return Builder<OfficeAction>
     */
    public function scopeWaitingForInstructions(Builder $query): Builder
    {
        return $query->open()
            ->whereNotNull('report_sent_on')
            ->where(function (Builder $inner): void {
                $inner->whereNull('response_instructions')
                    ->orWhere('response_instructions', '');
            });
    }

    /**
     * @param  Builder<OfficeAction>  $query
     * @return Builder<OfficeAction>
     */
    public function scopeForChecking(Builder $query): Builder
    {
        return $query->open()->whereIn('checking_status', [
            CheckingStatus::ForChecking,
            CheckingStatus::ForRevision,
        ]);
    }

    /**
     * @param  Builder<OfficeAction>  $query
     * @return Builder<OfficeAction>
     */
    public function scopeReadyToFile(Builder $query): Builder
    {
        return $query->open()
            ->whereNull('filing_date')
            ->whereNotNull('assignee_id')
            ->whereNotNull('report_sent_on')
            ->whereNotNull('response_instructions')
            ->where('response_instructions', '!=', '')
            ->whereNotNull('draft_prepared_on')
            ->where('checking_status', CheckingStatus::Checked)
            ->where('extension_status', '!=', ExtensionStatus::Pending)
            ->where(function (Builder $inner): void {
                $inner->where('client_approval_required', false)
                    ->orWhere('client_approval_status', ClientApprovalStatus::Approved);
            });
    }

    /**
     * @param  Builder<OfficeAction>  $query
     * @return Builder<OfficeAction>
     */
    public function scopePendingReporting(Builder $query): Builder
    {
        return $query->open()
            ->whereNotNull('filing_date')
            ->whereNull('filing_reported_on');
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function logger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by_id');
    }
}
