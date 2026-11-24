<?php

namespace App;

enum OfficeActionType: string
{
    case Ser = 'SER';
    case Sser = 'SSER';
    case Fer = 'FER';
    case NonFinal = 'NFOA';
    case Final = 'FOA';
    case Restriction = 'RR';
    case Advisory = 'ADV';
    case Other = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::Ser => 'SER — Substantive Examination Report',
            self::Sser => 'SSER — Subsequent Substantive Examination Report',
            self::Fer => 'FER — Formality Examination Report',
            self::NonFinal => 'Non-final office action',
            self::Final => 'Final office action',
            self::Restriction => 'Restriction requirement',
            self::Advisory => 'Advisory action',
            self::Other => 'Other',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $type): array => [
            'value' => $type->value,
            'label' => $type->label(),
        ], self::cases());
    }
}
