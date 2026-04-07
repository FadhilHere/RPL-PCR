<?php

namespace App\Enums;

enum NilaiHurufEnum: string
{
    case A  = 'A';
    case AB = 'AB';
    case B  = 'B';
    case BC = 'BC';
    case C  = 'C';
    case D  = 'D';
    case E  = 'E';

    public function label(): string
    {
        return $this->value;
    }

    public function diakui(): bool
    {
        return in_array($this, [self::A, self::AB, self::B, self::BC, self::C]);
    }

    /**
     * Konversi rata-rata nilai asesor (skala 1-5) ke nilai huruf
     */
    public static function fromRataRata(float $rataRata): self
    {
        return match(true) {
            $rataRata >= 5.0              => self::A,
            $rataRata >= 4.5              => self::AB,
            $rataRata >= 4.0              => self::B,
            $rataRata >= 3.5              => self::BC,
            $rataRata >= 3.0              => self::C,
            $rataRata >= 2.0              => self::D,
            default                       => self::E,
        };
    }
}
