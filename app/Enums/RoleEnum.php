<?php

namespace App\Enums;

enum RoleEnum: string
{
    case Peserta = 'peserta';
    case Asesor = 'asesor';
    case Admin = 'admin';

    public function label(): string
    {
        return match($this) {
            self::Peserta => 'Peserta',
            self::Asesor => 'Asesor',
            self::Admin => 'Admin',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Admin   => 'bg-[#F3E8FF] text-[#7c3aed]',
            self::Asesor  => 'bg-[#E8F0FE] text-[#1557b0]',
            self::Peserta => 'bg-[#E6F4EA] text-[#1e7e3e]',
        };
    }

    public function dashboardRoute(): string
    {
        return match($this) {
            self::Peserta => '/peserta/dashboard',
            self::Asesor => '/asesor/dashboard',
            self::Admin => '/admin/dashboard',
        };
    }
}
