<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\ActivityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $organization_id
 * @property int|null $user_id
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property int|null $client_id
 * @property string $event
 * @property array<string, mixed> $changes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['organization_id', 'user_id', 'subject_type', 'subject_id', 'client_id', 'event', 'changes'])]
class Activity extends Model
{
    /** @use HasFactory<ActivityFactory> */
    use BelongsToOrganization, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'changes' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param  Builder<Activity>  $query
     * @return Builder<Activity>
     */
    public function scopeForOfficeAction(Builder $query, OfficeAction $officeAction): Builder
    {
        return $query->where(function (Builder $inner) use ($officeAction): void {
            $inner->where(function (Builder $subject) use ($officeAction): void {
                $subject->where('subject_type', $officeAction->getMorphClass())
                    ->where('subject_id', $officeAction->id);
            })->orWhere(function (Builder $clientHistory) use ($officeAction): void {
                $clientHistory->where('client_id', $officeAction->client_id)
                    ->whereIn('subject_type', [ClientInstruction::class, Client::class]);
            });
        });
    }

    /**
     * @param  Builder<Activity>  $query
     * @return Builder<Activity>
     */
    public function scopeForClient(Builder $query, Client $client): Builder
    {
        return $query->where('client_id', $client->id);
    }
}
