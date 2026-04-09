<?php

namespace App\Actions\Asesor;

use App\Enums\StatusRplMataKuliahEnum;
use App\Models\PermohonanRpl;
use App\Models\RplMataKuliah;

class SimpanStatusMkAction
{
    public function __construct(
        private readonly SanitizeCatatanAsesorAction $sanitizer,
    ) {}

    public function execute(
        PermohonanRpl $permohonan,
        int $rplMkId,
        StatusRplMataKuliahEnum $status,
        ?string $catatan,
    ): void {
        $rplMk = RplMataKuliah::query()
            ->where('permohonan_rpl_id', $permohonan->id)
            ->findOrFail($rplMkId);

        $catatanAman = $this->sanitizer->execute($catatan);

        $rplMk->update([
            'status'         => $status,
            'catatan_asesor' => $catatanAman,
        ]);
    }
}
