<?php

namespace App\Actions\Asesor;

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
    }
}
