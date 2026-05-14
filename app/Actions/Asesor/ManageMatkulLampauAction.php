<?php

namespace App\Actions\Asesor;

use App\Models\MatkulLampau;
use App\Models\RplMataKuliah;

class ManageMatkulLampauAction
{
    public function create(RplMataKuliah $rplMk, array $data): MatkulLampau
    {
        return MatkulLampau::create([
            'rpl_mata_kuliah_id' => $rplMk->id,
            'kode_mk'            => null,
            'nama_mk'            => null,
            'sks'                => null,
            'nilai_huruf'        => null,
            'kode_mk_asesor'     => $data['kode_mk_asesor'],
            'nama_mk_asesor'     => $data['nama_mk_asesor'],
            'sks_asesor'         => $data['sks_asesor'],
            'nilai_huruf_asesor' => $data['nilai_huruf_asesor'] ?: null,
        ]);
    }

    public function updateAsesor(MatkulLampau $ml, array $data): MatkulLampau
    {
        $ml->update([
            'kode_mk_asesor'     => $data['kode_mk_asesor'],
            'nama_mk_asesor'     => $data['nama_mk_asesor'],
            'sks_asesor'         => $data['sks_asesor'],
            'nilai_huruf_asesor' => $data['nilai_huruf_asesor'] ?: null,
        ]);

        return $ml->fresh();
    }

    public function delete(MatkulLampau $ml): void
    {
        $ml->delete();
    }
}
