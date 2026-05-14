<?php

namespace App\Observers;

use App\Actions\Asesor\ReEvaluatePermohonanStatusAction;
use App\Models\RplMataKuliah;

class RplMataKuliahObserver
{
    public function __construct(
        private ReEvaluatePermohonanStatusAction $reEvaluate,
    ) {}

    public function updated(RplMataKuliah $rplMk): void
    {
        if (! $rplMk->wasChanged('status')) {
            return;
        }

        $permohonan = $rplMk->permohonanRpl;

        if (! $permohonan) {
            return;
        }

        $this->reEvaluate->execute($permohonan);
    }
}
