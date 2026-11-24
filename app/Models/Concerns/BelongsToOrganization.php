<?php

namespace App\Models\Concerns;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Limits every query to the signed-in user's firm.
 *
 * @mixin Model
 */
trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope('organization', function (Builder $builder): void {
            if (! Auth::hasUser()) {
                return;
            }

            $user = Auth::user();

            if (! $user instanceof User) {
                return;
            }

            $builder->where(
                $builder->getModel()->qualifyColumn('organization_id'),
                $user->organization_id,
            );
        });

        static::creating(function (Model $model): void {
            if ($model->getAttribute('organization_id') !== null || ! Auth::hasUser()) {
                return;
            }

            $user = Auth::user();

            if ($user instanceof User) {
                $model->setAttribute('organization_id', $user->organization_id);
            }
        });
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
