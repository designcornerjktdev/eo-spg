<?php

namespace App\Enums;

enum UserRole: string
{
    case Superadmin = 'superadmin';
    case Client = 'client';

    public function label(): string
    {
        return match ($this) {
            self::Superadmin => 'EO Admin',
            self::Client => 'Client',
        };
    }
}
