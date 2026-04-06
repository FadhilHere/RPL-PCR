<?php

namespace App\Actions\Asesor;

use App\Enums\StatusPermohonanEnum;
use App\Enums\StatusVerifikasiEnum;
use App\Models\PermohonanRpl;

class SimpanJadwalVerifikasiAction
{
    public function execute(
        PermohonanRpl $permohonan,
        string $jadwal,
        ?string $catatan,
        ?int $asesorId,
    ): void {
        abort_if(
            ! in_array($permohonan->status, [StatusPermohonanEnum::Diproses, StatusPermohonanEnum::Verifikasi]),
            403
        );

        $existing = $permohonan->verifikasiBersama()
            ->where('status', StatusVerifikasiEnum::Terjadwal)
            ->latest()
            ->first();

        if ($existing) {
            $existing->update([
                'asesor_id' => $asesorId,
                'jadwal'    => $jadwal,
                'catatan'   => $catatan ?: null,
            ]);
        } else {
            $permohonan->verifikasiBersama()->create([
                'asesor_id' => $asesorId,
                'jadwal'    => $jadwal,
                'catatan'   => $catatan ?: null,
                'status'    => StatusVerifikasiEnum::Terjadwal,
            ]);
        }
    }
}
