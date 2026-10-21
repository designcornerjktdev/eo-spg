<?php

namespace App\Enums;

enum SubmissionStatus: string
{
    case Submitted = 'submitted';
    case Void = 'void';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Terkirim',
            self::Void => 'Dibatalkan',
        };
    }
}
