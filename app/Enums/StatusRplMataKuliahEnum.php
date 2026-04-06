<?php

namespace App\Enums;

enum StatusRplMataKuliahEnum: string
{
    case Menunggu    = 'menunggu';
    case Diakui      = 'diakui';
    case TidakDiakui = 'tidak_diakui';

    public function label(): string
    {
        return match($this) {
            self::Menunggu    => 'Menunggu',
            self::Diakui      => 'Diakui',
            self::TidakDiakui => 'Tidak Diakui',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Menunggu    => 'bg-[#F1F3F4] text-[#5f6368]',
            self::Diakui      => 'bg-[#E6F4EA] text-[#1e7e3e]',
            self::TidakDiakui => 'bg-[#FCE8E6] text-[#c62828]',
        };
    }
}
