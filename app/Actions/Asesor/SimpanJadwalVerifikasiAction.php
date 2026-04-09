<?php

namespace App\Actions\Asesor;

use App\Enums\JenisRplEnum;
use App\Enums\StatusPermohonanEnum;
use App\Enums\StatusVerifikasiEnum;
use App\Models\Asesor;
use App\Models\PermohonanRpl;
use Illuminate\Support\Facades\DB;

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
            ! in_array($permohonan->status, [
                StatusPermohonanEnum::Diproses,
                StatusPermohonanEnum::Asesmen,
                StatusPermohonanEnum::Verifikasi,
            ]),
            403
        );

        // Asesor utama (pertama dalam daftar)
        $primaryAsesorId = $asesorIds[0] ?? null;

        DB::transaction(function () use ($permohonan, $jadwal, $catatan, $asesorIds, $primaryAsesorId) {
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

            // Set status permohonan berdasarkan jenis RPL.
            // Hanya transisi dari Diproses; jika sudah Asesmen/Verifikasi (re-jadwal) biarkan.
            if ($permohonan->status === StatusPermohonanEnum::Diproses) {
                $newStatus = $permohonan->jenis_rpl === JenisRplEnum::RplII
                    ? StatusPermohonanEnum::Asesmen
                    : StatusPermohonanEnum::Verifikasi;
                $permohonan->update(['status' => $newStatus]);
            }
        });
    }
}
