<?php

namespace App\Actions\Asesor;

use App\Enums\StatusPermohonanEnum;
use App\Models\Asesor;
use App\Models\BeritaAcara;
use App\Models\Penandatangan;
use App\Models\TahunAjaran;
use App\Enums\PosisiPenandatanganEnum;

class GenerateBeritaAcaraAction
{
    public function execute(
        Asesor $asesor,
        string $tanggalAsesmen,
        TahunAjaran $tahunAjaran,
    ): BeritaAcara {
        // Ambil semua permohonan yang terkait asesor ini melalui pivot
        $permohonanList = $asesor->permohonan()
            ->with(['peserta.user', 'rplMataKuliah'])
            ->whereIn('status', [
                StatusPermohonanEnum::Verifikasi,
                StatusPermohonanEnum::DalamReview,
                StatusPermohonanEnum::Disetujui,
            ])
            ->where('tahun_ajaran_id', $tahunAjaran->id)
            ->get();

        $jumlahPeserta    = $permohonanList->count();
        $jumlahHadir      = $jumlahPeserta; // default semua hadir, bisa diubah
        $jumlahTidakHadir = 0;

        // Ambil penandatangan aktif
        $pkiri  = Penandatangan::where('posisi', PosisiPenandatanganEnum::Kiri)->where('aktif', true)->orderBy('urutan')->first();
        $pkanan = Penandatangan::where('posisi', PosisiPenandatanganEnum::Kanan)->where('aktif', true)->orderBy('urutan')->first();

        $ba = BeritaAcara::create([
            'asesor_id'              => $asesor->id,
            'tahun_ajaran_id'        => $tahunAjaran->id,
            'penandatangan_kiri_id'  => $pkiri?->id,
            'penandatangan_kanan_id' => $pkanan?->id,
            'tanggal_asesmen'        => $tanggalAsesmen,
            'jumlah_peserta'         => $jumlahPeserta,
            'jumlah_hadir'           => $jumlahHadir,
            'jumlah_tidak_hadir'     => $jumlahTidakHadir,
            'is_locked'              => false,
            'generated_at'           => now(),
        ]);

        foreach ($permohonanList as $permohonan) {
            $totalSks = $permohonan->rplMataKuliah
                ->where('status->value', 'diakui')
                ->sum('sks_diakui');

            $ba->peserta()->create([
                'peserta_id'         => $permohonan->peserta_id,
                'permohonan_rpl_id'  => $permohonan->id,
                'hadir'              => true,
                'total_sks_diperoleh' => $totalSks,
            ]);
        }

        return $ba->fresh(['asesor.user', 'tahunAjaran', 'penandatanganKiri', 'penandatanganKanan', 'peserta.peserta.user']);
    }
}
