<?php

namespace App\Actions\Asesor;

use App\Enums\StatusPermohonanEnum;
use App\Enums\StatusVerifikasiEnum;
use App\Models\Asesor;
use App\Models\PermohonanRpl;

class SimpanJadwalVerifikasiAction
{
    /**
     * @param array<int> $asesorIds  IDs asesor yang di-assign ke permohonan ini
     */
    public function execute(
        PermohonanRpl $permohonan,
        string $jadwal,
        ?string $catatan,
        array $asesorIds,
    ): void {
        abort_if(
            ! in_array($permohonan->status, [StatusPermohonanEnum::Diproses, StatusPermohonanEnum::Verifikasi]),
            403
        );

        // Asesor utama (pertama dalam daftar)
        $primaryAsesorId = $asesorIds[0] ?? null;

        $existing = $permohonan->verifikasiBersama()
            ->where('status', StatusVerifikasiEnum::Terjadwal)
            ->latest()
            ->first();

        if ($existing) {
            $existing->update([
                'asesor_id' => $primaryAsesorId,
                'jadwal'    => $jadwal,
                'catatan'   => $catatan ?: null,
            ]);
        } else {
            $permohonan->verifikasiBersama()->create([
                'asesor_id' => $primaryAsesorId,
                'jadwal'    => $jadwal,
                'catatan'   => $catatan ?: null,
                'status'    => StatusVerifikasiEnum::Terjadwal,
            ]);
        }

        // Sync semua asesor ke pivot asesor_permohonan
        if (! empty($asesorIds)) {
            $asesorDbIds = Asesor::whereIn('id', $asesorIds)->pluck('id')->all();
            $permohonan->asesor()->sync($asesorDbIds);
        }
    }
}
