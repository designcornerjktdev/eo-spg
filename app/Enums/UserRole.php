<?php

namespace App\Enums;

enum UserRole: string
{
    case Superadmin = 'superadmin';
    /** EO operations staff: runs campaigns, forms and entries, but not accounts or company settings. */
    case Admin = 'admin';
    case Client = 'client';

    /**
     * Roles that may sign in with an email and reset their own password; clients use an admin-managed username.
     *
     * @return array<int, string>
     */
    public static function withEmailLogin(): array
    {
        return [self::Superadmin->value, self::Admin->value];
    }

    public function label(): string
    {
        return match ($this) {
            self::Superadmin => 'Super admin',
            self::Admin => 'Admin',
            self::Client => 'Client',
        };
    }
}
