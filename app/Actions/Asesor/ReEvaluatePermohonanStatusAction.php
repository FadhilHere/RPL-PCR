<?php

namespace App\Actions\Asesor;

use App\Enums\StatusPermohonanEnum;
use App\Models\PermohonanRpl;
use Illuminate\Support\Facades\DB;

class ReEvaluatePermohonanStatusAction
{
    /**
     * Re-evaluate status PermohonanRpl berdasarkan kondisi RplMataKuliah saat ini.
     * Hanya jalan kalau permohonan sudah final (Disetujui/Ditolak).
     * Kalau status berubah dan permohonan sebelumnya sudah dirilis,
     * reset state rilis agar admin BAAK harus rilis ulang.
     *
     * @return bool true kalau status ter-update.
     */
    public function execute(PermohonanRpl $permohonan): bool
    {
        if (! in_array($permohonan->status, [
            StatusPermohonanEnum::Disetujui,
            StatusPermohonanEnum::Ditolak,
        ])) {
            return false;
        }

        $permohonan->loadMissing(['rplMataKuliah.mataKuliah', 'programStudi']);

        $statusBaru = $permohonan->hitungStatusByAturan();

        if ($statusBaru === $permohonan->status) {
            return false;
        }

        return DB::transaction(function () use ($permohonan, $statusBaru) {
            $update = ['status' => $statusBaru];

            // Reset rilis kalau sudah pernah dirilis — peserta tidak boleh lihat
            // status yang sudah tidak akurat. Admin BAAK harus rilis ulang.
            if ($permohonan->sudahDirilis()) {
                $update['dirilis_pada']         = null;
                $update['dirilis_oleh_user_id'] = null;
            }

            $permohonan->update($update);

            return true;
        });
    }
}
