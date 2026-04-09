<?php

namespace App\Actions\Asesor;

use App\Enums\StatusPermohonanEnum;
use App\Enums\StatusRplMataKuliahEnum;
use App\Enums\StatusVerifikasiEnum;
use App\Models\PermohonanRpl;
use Illuminate\Support\Facades\DB;

class FinalisasiPermohonanAction
{
    /**
     * Finalisasi permohonan dari status Asesmen/Verifikasi → Disetujui/Ditolak.
     *
     * Aturan: jika total SKS dari MK ber-status Diakui ≥ 50% dari total SKS prodi,
     * permohonan disetujui; jika kurang, ditolak.
     *
     * Jika verifikasi bersama masih Terjadwal, otomatis ditandai Selesai.
     *
     * @throws \DomainException jika masih ada MK ber-status Menunggu.
     */
    public function execute(PermohonanRpl $permohonan): StatusPermohonanEnum
    {
        abort_if(
            ! in_array($permohonan->status, [
                StatusPermohonanEnum::Asesmen,
                StatusPermohonanEnum::Verifikasi,
            ]),
            403
        );

        $permohonan->loadMissing(['rplMataKuliah.mataKuliah', 'programStudi', 'verifikasiBersama']);

        $masihMenunggu = $permohonan->rplMataKuliah
            ->contains(fn ($mk) => $mk->status === StatusRplMataKuliahEnum::Menunggu);

        if ($masihMenunggu) {
            throw new \DomainException('Masih ada mata kuliah yang belum dinilai.');
        }

        return DB::transaction(function () use ($permohonan) {
            // Auto-finish verifikasi bersama jika masih Terjadwal
            $vb = $permohonan->verifikasiBersama
                ->firstWhere('status', StatusVerifikasiEnum::Terjadwal);

            if ($vb) {
                $vb->update(['status' => StatusVerifikasiEnum::Selesai]);
            }

            // Hitung 50% SKS rule
            $sksDiakui = $permohonan->rplMataKuliah
                ->where('status', StatusRplMataKuliahEnum::Diakui)
                ->sum(fn ($mk) => $mk->mataKuliah->sks ?? 0);

            $totalSks = $permohonan->programStudi->total_sks ?? 0;
            $batasMin = $totalSks * 0.50;

            $finalStatus = ($totalSks > 0 && $sksDiakui >= $batasMin)
                ? StatusPermohonanEnum::Disetujui
                : StatusPermohonanEnum::Ditolak;

            $permohonan->update(['status' => $finalStatus]);

            return $finalStatus;
        });
    }
}
