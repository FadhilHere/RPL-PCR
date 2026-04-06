<?php

namespace App\Actions\Asesor;

use App\Enums\StatusRplMataKuliahEnum;
use App\Models\RplMataKuliah;

class HitungKeputusanMkAction
{
    /**
     * Rata-rata nilai_asesor seluruh Sub CPMK dalam MK.
     * < 3  → TidakDiakui
     * >= 3 → Diakui
     */
    public function execute(RplMataKuliah $rplMk): StatusRplMataKuliahEnum
    {
        $nilaiList = $rplMk->asesmenMandiri
            ->map(fn($asm) => $asm->nilaiAsesor?->nilai)
            ->filter(fn($v) => $v !== null);

        if ($nilaiList->isEmpty()) {
            return StatusRplMataKuliahEnum::Menunggu;
        }

        $rataRata = $nilaiList->average();

        return $rataRata >= 3
            ? StatusRplMataKuliahEnum::Diakui
            : StatusRplMataKuliahEnum::TidakDiakui;
    }

    /**
     * Hitung rata-rata nilai asesor untuk satu RplMataKuliah.
     */
    public function rataRata(RplMataKuliah $rplMk): ?float
    {
        $nilaiList = $rplMk->asesmenMandiri
            ->map(fn($asm) => $asm->nilaiAsesor?->nilai)
            ->filter(fn($v) => $v !== null);

        return $nilaiList->isNotEmpty() ? round($nilaiList->average(), 2) : null;
    }
}
