<?php

namespace App\Actions\Peserta;

use App\Enums\SemesterEnum;
use App\Enums\StatusPermohonanEnum;
use App\Models\PermohonanRpl;
use App\Models\Peserta;
use App\Models\ProgramStudi;

class BuatPermohonanAction
{
    public function execute(
        Peserta $peserta,
        ProgramStudi $prodi,
        ?int $tahunAjaranId = null,
        ?SemesterEnum $semester = null,
    ): PermohonanRpl {
        $year  = now()->year;
        $count = PermohonanRpl::where('program_studi_id', $prodi->id)
            ->whereYear('created_at', $year)
            ->count() + 1;

        $nomor = sprintf('RPL-%d-%s-%04d', $year, $prodi->kode, $count);

        return PermohonanRpl::create([
            'peserta_id'        => $peserta->id,
            'program_studi_id'  => $prodi->id,
            'nomor_permohonan'  => $nomor,
            'status'            => StatusPermohonanEnum::Diajukan,
            'tanggal_pengajuan' => now(),
            'tahun_ajaran_id'   => $tahunAjaranId,
            'semester'          => $semester,
        ]);
    }
}
