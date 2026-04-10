<?php

namespace App\Enums;

enum JenisRplEnum: string
{
    case RplI = 'rpl_i';   // Transfer Kredit — dari perguruan tinggi lain
    case RplII = 'rpl_ii';  // Pengakuan Pengalaman Kerja — non-formal/informal

    public function label(): string
    {
        return match ($this) {
            self::RplI => 'Transfer Kredit',
            self::RplII => 'Perolehan Kredit',
        };
    }
}
