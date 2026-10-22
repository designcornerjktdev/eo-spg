<?php

namespace App\Enums;

enum FormStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Locked = 'locked';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Published',
            self::Locked => 'Locked',
        };
    }
}
