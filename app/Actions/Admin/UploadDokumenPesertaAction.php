<?php

namespace App\Actions\Admin;

use App\Enums\JenisDokumenEnum;
use App\Models\DokumenBukti;
use App\Models\Peserta;
use Illuminate\Http\UploadedFile;

class UploadDokumenPesertaAction
{
    public function execute(
        Peserta $peserta,
        UploadedFile $file,
        string $namaDokumen,
        JenisDokumenEnum $jenisDokumen,
        ?string $keterangan,
        int $adminUserId,
    ): DokumenBukti {
        $path = $file->store('dokumen/' . $peserta->id, 'local');

        return DokumenBukti::create([
            'peserta_id'          => $peserta->id,
            'nama_dokumen'        => $namaDokumen,
            'jenis_dokumen'       => $jenisDokumen,
            'berkas'              => $path,
            'keterangan'          => $keterangan ?: null,
            'uploaded_by_user_id' => $adminUserId,
        ]);
    }
}
