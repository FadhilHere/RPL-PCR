<?php

namespace App\Enums;

enum StatusVerifikasiEnum: string
{
    case Terjadwal = 'terjadwal';
    case Selesai   = 'selesai';

    public function label(): string
    {
        return match($this) {
            self::Terjadwal => 'Terjadwal',
            self::Selesai   => 'Selesai',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Terjadwal => 'bg-[#FFF8E1] text-[#b45309]',
            self::Selesai   => 'bg-[#E6F4EA] text-[#1e7e3e]',
        };
    }
}
