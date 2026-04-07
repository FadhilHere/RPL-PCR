<?php

namespace App\Actions\Admin;

use App\Enums\PosisiPenandatanganEnum;
use App\Models\Penandatangan;

class KelolaPenandatanganAction
{
    public function create(
        string $nama,
        string $jabatan,
        ?string $nip,
        PosisiPenandatanganEnum $posisi,
        int $urutan = 1,
    ): Penandatangan {
        return Penandatangan::create([
            'nama'    => $nama,
            'jabatan' => $jabatan,
            'nip'     => $nip ?: null,
            'posisi'  => $posisi,
            'aktif'   => true,
            'urutan'  => $urutan,
        ]);
    }

    public function update(Penandatangan $penandatangan, string $nama, string $jabatan, ?string $nip, PosisiPenandatanganEnum $posisi, bool $aktif, int $urutan): void
    {
        $penandatangan->update(compact('nama', 'jabatan', 'nip', 'posisi', 'aktif', 'urutan'));
    }

    public function delete(Penandatangan $penandatangan): void
    {
        $penandatangan->delete();
    }
}
