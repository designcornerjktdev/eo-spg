<?php

namespace App\Enums;

enum UserRole: string
{
    case Superadmin = 'superadmin';
    case Client = 'client';
    case Spg = 'spg';

    public function label(): string
    {
        return match ($this) {
            self::Superadmin => 'Superadmin (EO)',
            self::Client => 'Client',
            self::Spg => 'SPG',
        };
    }
}
