<?php

namespace App\Enums;

enum SemesterEnum: string
{
    case Ganjil = 'ganjil';
    case Genap  = 'genap';

    /** @return array<string, string> Format: ['value' => 'label'] — untuk x-form.select */
    public static function options(): array
    {
        return array_column(
            array_map(fn($case) => [$case->value, $case->label()], self::cases()),
            1, 0
        );
    }

    public function label(): string
    {
        return match($this) {
            self::Ganjil => 'Ganjil',
            self::Genap  => 'Genap',
        };
    }
}
