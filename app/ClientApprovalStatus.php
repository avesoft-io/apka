<?php

namespace App;

enum ClientApprovalStatus: string
{
    case NotRequired = 'not_required';
    case Pending = 'pending';
    case Approved = 'approved';

    public function label(): string
    {
        return match ($this) {
            self::NotRequired => 'Not required',
            self::Pending => 'Pending',
            self::Approved => 'Approved',
        };
    }
}
