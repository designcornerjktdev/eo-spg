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
            self::ShortText => 'Short text',
            self::LongText => 'Long text',
            self::Number => 'Number',
            self::Phone => 'Phone number',
            self::SingleChoice => 'Single choice',
            self::MultipleChoice => 'Multiple choice',
            self::YesNo => 'Yes/No',
            self::Date => 'Date',
        };
    }

    public function canShowOnDashboard(): bool
    {
        // Yes/No questions feed the qualification count, not their own dashboard chart.
        return in_array($this, [self::Number, self::SingleChoice, self::MultipleChoice], true);
    }
}
