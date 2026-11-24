<?php

namespace App;

use App\Models\Activity;
use App\Models\User;
use BackedEnum;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    /**
     * @param  list<string>  $fields
     */
    public function created(Model $subject, User $actor, ?int $clientId = null, array $fields = []): Activity
    {
        $source = $fields === [] ? array_keys($subject->getAttributes()) : $fields;
        $changes = [];

        foreach ($source as $field) {
            if ($this->ignored($field)) {
                continue;
            }

            $value = $this->normalize($subject->getAttribute($field), $field);

            if ($value === null || $value === '') {
                continue;
            }

            $changes[$field] = [
                'before' => null,
                'after' => $value,
            ];
        }

        return $this->store($subject, $actor, 'created', $changes, $clientId);
    }

    public function updated(Model $subject, User $actor, ?int $clientId = null): ?Activity
    {
        $changes = [];

        foreach ($subject->getDirty() as $field => $value) {
            if ($this->ignored($field)) {
                continue;
            }

            $changes[$field] = [
                'before' => $this->normalize($subject->getOriginal($field), $field),
                'after' => $this->normalize($value, $field),
            ];
        }

        $subject->save();

        if ($changes === []) {
            return null;
        }

        return $this->store($subject, $actor, 'updated', $changes, $clientId);
    }

    public function deleted(Model $subject, User $actor, string $summary, ?int $clientId = null): Activity
    {
        return $this->store($subject, $actor, 'deleted', [
            'record' => [
                'before' => $summary,
                'after' => null,
            ],
        ], $clientId);
    }

    /**
     * @param  array<string, array{before: bool|float|int|string|null, after: bool|float|int|string|null}>  $changes
     */
    private function store(Model $subject, User $actor, string $event, array $changes, ?int $clientId): Activity
    {
        return Activity::query()->create([
            'organization_id' => $actor->organization_id,
            'user_id' => $actor->id,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'client_id' => $clientId,
            'event' => $event,
            'changes' => $changes,
        ]);
    }

    private function ignored(string $field): bool
    {
        return in_array($field, ['id', 'created_at', 'updated_at', 'password', 'remember_token'], true);
    }

    private function normalize(mixed $value, string $field): bool|float|int|string|null
    {
        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        if ($value instanceof CarbonInterface) {
            return $value->toDateString();
        }

        if (is_bool($value) || is_int($value) || is_float($value) || $value === null) {
            return $value;
        }

        if (! is_string($value)) {
            return null;
        }

        if (str_ends_with($field, '_id') && preg_match('/^\d+$/', $value) === 1) {
            return (int) $value;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}(?:[ T].*)?$/', $value) === 1) {
            return substr($value, 0, 10);
        }

        return $value;
    }
}
