<?php

namespace App\Actions\Admin;

use App\Models\PermohonanRpl;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RilisHasilPermohonanAction
{
    /**
     * Rilis hasil permohonan-permohonan terpilih ke peserta.
     *
     * Hanya permohonan Disetujui/Ditolak yang belum dirilis yang akan diproses
     * (scope siapDirilis sebagai filter pengaman terhadap ID arbitrary).
     *
     * @param  int[]  $permohonanIds
     * @return int Jumlah permohonan yang berhasil dirilis.
     */
    public function execute(array $permohonanIds, User $user): int
    {
        if (empty($permohonanIds)) {
            return 0;
        }

        return DB::transaction(function () use ($permohonanIds, $user) {
            return PermohonanRpl::query()
                ->siapDirilis()
                ->whereIn('id', $permohonanIds)
                ->update([
                    'dirilis_pada'         => now(),
                    'dirilis_oleh_user_id' => $user->id,
                ]);
        });
    }
}
