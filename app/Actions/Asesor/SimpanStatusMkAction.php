<?php

namespace App\Actions\Asesor;

use App\Enums\StatusPermohonanEnum;
use App\Enums\StatusRplMataKuliahEnum;
use App\Models\PermohonanRpl;
use App\Models\RplMataKuliah;

class SimpanStatusMkAction
{
    public function execute(
        PermohonanRpl $permohonan,
        int $rplMkId,
        StatusRplMataKuliahEnum $status,
        ?string $catatan,
    ): void {
        RplMataKuliah::findOrFail($rplMkId)->update([
            'status'         => $status,
            'catatan_asesor' => $catatan ?: null,
        ]);

        $masihMenunggu = RplMataKuliah::where('permohonan_rpl_id', $permohonan->id)
            ->whereNotIn('status', [
                StatusRplMataKuliahEnum::Diakui->value,
                StatusRplMataKuliahEnum::TidakDiakui->value,
            ])
            ->exists();

        // Ambil status terbaru dari DB (bukan dari cache Livewire)
        $statusSaatIni = PermohonanRpl::find($permohonan->id)?->status;

        if (! $masihMenunggu && $statusSaatIni === StatusPermohonanEnum::DalamReview) {
            $permohonan->update(['status' => StatusPermohonanEnum::Disetujui]);
        }
    }
}
