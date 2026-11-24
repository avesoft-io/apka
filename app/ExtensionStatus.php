<?php

namespace App;

enum ExtensionStatus: string
{
    case None = 'none';
    case Pending = 'pending';
    case Confirmed = 'confirmed';

    public function label(): string
    {
        return match ($this) {
            self::None => 'None',
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
        };
    }
}
