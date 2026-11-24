<?php

namespace App;

enum OfficeActionOutcome: string
{
    case Open = 'open';
    case Completed = 'completed';
    case Abandoned = 'abandoned';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Completed => 'Completed',
            self::Abandoned => 'Abandoned',
        };
    }
}
