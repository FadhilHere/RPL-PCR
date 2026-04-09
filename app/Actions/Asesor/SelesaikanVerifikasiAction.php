<?php

namespace App\Actions\Asesor;

use App\Enums\StatusPermohonanEnum;
use App\Enums\StatusVerifikasiEnum;
use App\Models\PermohonanRpl;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class SelesaikanVerifikasiAction
{
    /**
     * Tutup verifikasi bersama (upload BA + catatan hasil).
     * Tidak mengubah status permohonan — finalisasi permohonan dilakukan
     * lewat FinalisasiPermohonanAction (tombol "Selesai" oleh asesor).
     */
    public function execute(
        PermohonanRpl $permohonan,
        ?UploadedFile $berkasBA,
        string $catatanHasil = '',
    ): void {
        abort_if(
            ! in_array($permohonan->status, [
                StatusPermohonanEnum::Asesmen,
                StatusPermohonanEnum::Verifikasi,
            ]),
            403
        );

        DB::transaction(function () use ($permohonan, $berkasBA, $catatanHasil) {
            $baPath = null;
            if ($berkasBA) {
                $ext    = strtolower($berkasBA->getClientOriginalExtension());
                $baPath = $berkasBA->storeAs(
                    "verifikasi/permohonan_{$permohonan->id}",
                    'BA_' . now()->format('Ymd_His') . '.' . $ext,
                    'local'
                );
            }

            $vb = $permohonan->verifikasiBersama()
                ->where('status', StatusVerifikasiEnum::Terjadwal)
                ->latest()
                ->first();

            if ($vb) {
                $vb->update([
                    'status'        => StatusVerifikasiEnum::Selesai,
                    'berkas'        => $baPath ?? $vb->berkas,
                    'catatan_hasil' => $catatanHasil ?: null,
                ]);
            }
        });
    }
}
