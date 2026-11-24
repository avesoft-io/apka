<?php

namespace App;

enum CheckingStatus: string
{
    case None = 'none';
    case ForChecking = 'for_checking';
    case ForRevision = 'for_revision';
    case Checked = 'checked';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Not sent for checking',
            self::ForChecking => 'For checking',
            self::ForRevision => 'For revision',
            self::Checked => 'Checked',
        };
    }
}
