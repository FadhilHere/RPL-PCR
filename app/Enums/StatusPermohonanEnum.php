<?php

namespace App\Enums;

enum StatusPermohonanEnum: string
{
    case Draf        = 'draf';
    case Diajukan    = 'diajukan';
    case Diproses    = 'diproses';     // Admin assign MK, peserta isi asesmen mandiri
    case Asesmen     = 'asesmen';      // RplII (perolehan kredit) — admin set jadwal, asesor evaluasi
    case Verifikasi  = 'verifikasi';   // RplI (transfer kredit) — admin set jadwal, asesor verifikasi
    case Disetujui   = 'disetujui';
    case Ditolak     = 'ditolak';

    public function label(): string
    {
        return match($this) {
            self::Draf       => 'Draf',
            self::Diajukan   => 'Diajukan',
            self::Diproses   => 'Diproses',
            self::Asesmen    => 'Asesmen',
            self::Verifikasi => 'Verifikasi',
            self::Disetujui  => 'Disetujui',
            self::Ditolak    => 'Ditolak',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Draf       => 'bg-[#F1F3F4] text-[#5f6368]',
            self::Diajukan   => 'bg-[#E8F0FE] text-[#1557b0]',
            self::Diproses   => 'bg-[#E8F0FE] text-[#1557b0]',
            self::Asesmen    => 'bg-[#FFF8E1] text-[#b45309]',
            self::Verifikasi => 'bg-[#FFF8E1] text-[#b45309]',
            self::Disetujui  => 'bg-[#E6F4EA] text-[#1e7e3e]',
            self::Ditolak    => 'bg-[#FCE8E6] text-[#c62828]',
        };
    }
}
