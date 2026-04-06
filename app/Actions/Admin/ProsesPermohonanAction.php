<?php

namespace App\Actions\Admin;

use App\Enums\StatusPermohonanEnum;
use App\Enums\StatusRplMataKuliahEnum;
use App\Models\MataKuliah;
use App\Models\PermohonanRpl;
use App\Models\RplMataKuliah;
use Illuminate\Support\Facades\DB;

class ProsesPermohonanAction
{
    public function execute(PermohonanRpl $permohonan, int $prodiId): void
    {
        abort_if($permohonan->status !== StatusPermohonanEnum::Diajukan, 403);

        DB::transaction(function () use ($permohonan, $prodiId) {
            if ($prodiId !== $permohonan->program_studi_id) {
                $permohonan->update(['program_studi_id' => $prodiId]);
            }

            MataKuliah::where('program_studi_id', $prodiId)
                ->where('bisa_rpl', true)
                ->get()
                ->each(fn($mk) => RplMataKuliah::firstOrCreate(
                    ['permohonan_rpl_id' => $permohonan->id, 'mata_kuliah_id' => $mk->id],
                    ['status' => StatusRplMataKuliahEnum::Menunggu],
                ));

            $permohonan->update(['status' => StatusPermohonanEnum::Diproses]);
        });
    }
}
