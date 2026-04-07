<?php

namespace App\Enums;

enum RoleEnum: string
{
    case Peserta  = 'peserta';
    case Asesor   = 'asesor';
    case Admin    = 'admin';
    case AdminPmb = 'admin_pmb';
    case AdminBaak = 'admin_baak';

    public function label(): string
    {
        return match($this) {
            self::Peserta   => 'Peserta',
            self::Asesor    => 'Asesor',
            self::Admin     => 'Admin',
            self::AdminPmb  => 'Admin PMB',
            self::AdminBaak => 'Admin BAAK',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Admin     => 'bg-[#F3E8FF] text-[#7c3aed]',
            self::AdminPmb  => 'bg-[#FCE8F3] text-[#be185d]',
            self::AdminBaak => 'bg-[#FFF0E0] text-[#b45309]',
            self::Asesor    => 'bg-[#E8F0FE] text-[#1557b0]',
            self::Peserta   => 'bg-[#E6F4EA] text-[#1e7e3e]',
        };
    }

    public function dashboardRoute(): string
    {
        return match($this) {
            self::Peserta   => '/peserta/dashboard',
            self::Asesor    => '/asesor/dashboard',
            self::Admin     => '/admin/dashboard',
            self::AdminPmb  => '/admin-pmb/dashboard',
            self::AdminBaak => '/admin-baak/dashboard',
        };
    }

    public function isAdminFamily(): bool
    {
        return in_array($this, [self::Admin, self::AdminPmb, self::AdminBaak]);
    }
}
