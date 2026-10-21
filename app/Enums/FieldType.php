<?php

namespace App\Enums;

enum FieldType: string
{
    case ShortText = 'short_text';
    case LongText = 'long_text';
    case Number = 'number';
    case Phone = 'phone';
    case SingleChoice = 'single_choice';
    case MultipleChoice = 'multiple_choice';
    case YesNo = 'yes_no';
    case Date = 'date';

    public function label(): string
    {
        return match ($this) {
            self::ShortText => 'Teks pendek',
            self::LongText => 'Teks panjang',
            self::Number => 'Angka',
            self::Phone => 'Nomor telepon',
            self::SingleChoice => 'Pilihan tunggal',
            self::MultipleChoice => 'Pilihan ganda',
            self::YesNo => 'Ya/Tidak',
            self::Date => 'Tanggal',
        };
    }

    public function canShowOnDashboard(): bool
    {
        return in_array($this, [self::Number, self::SingleChoice, self::MultipleChoice, self::YesNo], true);
    }
}
