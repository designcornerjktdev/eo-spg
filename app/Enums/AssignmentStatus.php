<?php

namespace App\Enums;

enum AssignmentStatus: string
{
    case Scheduled = 'scheduled';
    case Active = 'active';
    case Done = 'done';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Terjadwal',
            self::Active => 'Aktif',
            self::Done => 'Selesai',
            self::Cancelled => 'Batal',
        };
    }
}
