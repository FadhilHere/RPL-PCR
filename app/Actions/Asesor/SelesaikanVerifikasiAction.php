<?php

namespace App\Actions\Asesor;

use App\Enums\StatusPermohonanEnum;
use App\Enums\StatusRplMataKuliahEnum;
use App\Enums\StatusVerifikasiEnum;
use App\Models\PermohonanRpl;
use App\Models\RplMataKuliah;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class SelesaikanVerifikasiAction
{
    public function execute(
        PermohonanRpl $permohonan,
        ?UploadedFile $berkasBA,
        string $catatanHasil = '',
    ): void {
        abort_if($permohonan->status !== StatusPermohonanEnum::Verifikasi, 403);

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

            $permohonan->update(['status' => StatusPermohonanEnum::DalamReview]);

            $masihMenunggu = RplMataKuliah::where('permohonan_rpl_id', $permohonan->id)
                ->whereNotIn('status', [
                    StatusRplMataKuliahEnum::Diakui->value,
                    StatusRplMataKuliahEnum::TidakDiakui->value,
                ])
                ->exists();

            if (! $masihMenunggu) {
                $permohonan->update(['status' => StatusPermohonanEnum::Disetujui]);
            }
        });
    }
}
