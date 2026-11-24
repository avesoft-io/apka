<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property string|null $code
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['organization_id', 'name', 'code'])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use BelongsToOrganization, HasFactory;

    /**
     * @return HasMany<ClientInstruction, $this>
     */
    public function instructions(): HasMany
    {
        return $this->hasMany(ClientInstruction::class);
    }

    /**
     * @return HasMany<OfficeAction, $this>
     */
    public function officeActions(): HasMany
    {
        return $this->hasMany(OfficeAction::class);
    }
}
