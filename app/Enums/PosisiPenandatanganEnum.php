<?php

namespace App\Enums;

enum PosisiPenandatanganEnum: string
{
    case Kiri  = 'kiri';
    case Kanan = 'kanan';
    case Wadir = 'wadir';

    public function label(): string
    {
        return match($this) {
            self::Kiri  => 'Kiri',
            self::Kanan => 'Kanan',
            self::Wadir => 'Wakil Direktur',
        };
    }
}
