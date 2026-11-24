<?php

namespace App;

enum UserRole: string
{
    case Staff = 'staff';
    case Supervisor = 'supervisor';

    public function label(): string
    {
        return match ($this) {
            self::Staff => 'Staff',
            self::Supervisor => 'Supervisor',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $role): array => [
            'value' => $role->value,
            'label' => $role->label(),
        ], self::cases());
    }
}
