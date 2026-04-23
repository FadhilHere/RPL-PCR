<?php

namespace App\Actions\Profil;

use App\Models\Peserta;
use Illuminate\Http\UploadedFile;

class SimpanBiodataPesertaAction
{
    /**
     * @param array<string, string|null> $biodata
     */
    public function execute(Peserta $peserta, string $nama, string $email, array $biodata, ?UploadedFile $foto = null): void
    {
        $peserta->user->update([
            'nama' => $nama,
            'email' => $email,
        ]);

        $fotoPath = null;
        if ($foto) {
            $fotoPath = $foto->storeAs(
                'peserta/foto',
                uniqid('foto_', true) . '.' . $foto->getClientOriginalExtension(),
                'public'
            );
        }

        $payload = array_filter([
            'nik' => $biodata['nik'] ?? null,
            'telepon' => $biodata['telepon'] ?? null,
            'telepon_faks' => $biodata['telepon_faks'] ?? null,
            'alamat' => $biodata['alamat'] ?? null,
            'kota' => $biodata['kota'] ?? null,
            'provinsi' => $biodata['provinsi'] ?? null,
            'kode_pos' => $biodata['kode_pos'] ?? null,
            'tempat_lahir' => $biodata['tempat_lahir'] ?? null,
            'tanggal_lahir' => $biodata['tanggal_lahir'] ?? null,
            'jenis_kelamin' => $biodata['jenis_kelamin'] ?? null,
            'agama' => $biodata['agama'] ?? null,
            'golongan_pangkat' => $biodata['golongan_pangkat'] ?? null,
            'instansi' => $biodata['instansi'] ?? null,
            'pekerjaan' => $biodata['pekerjaan'] ?? null,
            'institusi_asal' => $biodata['institusi_asal'] ?? null,
            'program_studi_asal' => $biodata['program_studi_asal'] ?? null,
            'peringkat_akreditasi_asal' => $biodata['peringkat_akreditasi_asal'] ?? null,
        ], static fn($value) => $value !== null);

        if ($fotoPath) {
            $payload['foto'] = $fotoPath;
        }

        $payload['semester'] = $biodata['semester'] ?? null;

        $peserta->update($payload);
    }
}
