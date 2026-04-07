<?php

namespace App\Actions\Asesor;

use App\Enums\JenisRplEnum;
use App\Enums\NilaiHurufEnum;
use App\Enums\StatusRplMataKuliahEnum;
use App\Models\RplMataKuliah;

class HitungKeputusanMkAction
{
    /**
     * Hitung dan kembalikan keputusan status MK.
     *
     * RPL I (Transfer Kredit): gunakan nilai_transfer huruf langsung.
     * RPL II (Pengalaman Kerja): rata-rata nilai_asesor dari sub CPMK.
     *   < 3  → TidakDiakui
     *   >= 3 → Diakui
     */
    public function execute(RplMataKuliah $rplMk): StatusRplMataKuliahEnum
    {
        $permohonan = $rplMk->permohonan_rpl_id
            ? $rplMk->permohonanRpl
            : null;

        $jenisRpl = $permohonan?->jenis_rpl ?? JenisRplEnum::RplII;

        if ($jenisRpl === JenisRplEnum::RplI) {
            return $this->executeTransfer($rplMk);
        }

        return $this->executePerolehan($rplMk);
    }

    private function executeTransfer(RplMataKuliah $rplMk): StatusRplMataKuliahEnum
    {
        if (! $rplMk->nilai_transfer) {
            return StatusRplMataKuliahEnum::Menunggu;
        }

        $nilaiEnum = NilaiHurufEnum::from($rplMk->nilai_transfer);

        return $nilaiEnum->diakui()
            ? StatusRplMataKuliahEnum::Diakui
            : StatusRplMataKuliahEnum::TidakDiakui;
    }

    private function executePerolehan(RplMataKuliah $rplMk): StatusRplMataKuliahEnum
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
     * Hitung rata-rata nilai asesor (RPL II only).
     */
    public function rataRata(RplMataKuliah $rplMk): ?float
    {
        $nilaiList = $rplMk->asesmenMandiri
            ->map(fn($asm) => $asm->nilaiAsesor?->nilai)
            ->filter(fn($v) => $v !== null);

        return $nilaiList->isNotEmpty() ? round($nilaiList->average(), 2) : null;
    }
}
