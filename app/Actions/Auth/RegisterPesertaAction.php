<?php

namespace App\Actions\Auth;

use App\Enums\JenisDokumenEnum;
use App\Enums\RoleEnum;
use App\Models\DokumenBukti;
use App\Models\Peserta;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RegisterPesertaAction
{
    public function execute(
        string $nama,
        string $email,
        string $password,
        string $jenisKelamin,
        string $tanggalLahir,
        string $alamat,
        string $kota,
        string $provinsi,
        ?string $kodePos,
        string $telepon,
        ?string $foto,
        bool $isDoPcr,
        string $semester,
        UploadedFile $berkasCV,
        ?UploadedFile $berkasTranskrip,
        ?UploadedFile $berkasKeteranganMK,
    ): Peserta {
        return DB::transaction(function () use (
            $nama, $email, $password, $jenisKelamin, $tanggalLahir,
            $alamat, $kota, $provinsi, $kodePos, $telepon, $foto,
            $isDoPcr, $semester, $berkasCV, $berkasTranskrip, $berkasKeteranganMK
        ) {
            $user = User::create([
                'nama'     => $nama,
                'email'    => $email,
                'password' => Hash::make($password),
                'role'     => RoleEnum::Peserta,
                'aktif'    => false,
            ]);

            $role = Role::firstOrCreate(['name' => 'peserta', 'guard_name' => 'web']);
            $user->assignRole($role);

            $tahunAjaran = TahunAjaran::aktif()->first();

            $peserta = Peserta::create([
                'user_id'         => $user->id,
                'jenis_kelamin'   => $jenisKelamin,
                'tanggal_lahir'   => $tanggalLahir,
                'alamat'          => $alamat,
                'kota'            => $kota,
                'provinsi'        => $provinsi,
                'kode_pos'        => $kodePos ?: null,
                'telepon'         => $telepon,
                'foto'            => $foto,
                'is_do_pcr'       => $isDoPcr,
                'semester'        => $semester,
                'tahun_ajaran_id' => $tahunAjaran?->id,
            ]);

            // Simpan berkas registrasi
            $this->simpanBerkas($peserta, $user->id, $berkasCV, JenisDokumenEnum::Cv, 'CV / Daftar Riwayat Hidup');

            if ($berkasTranskrip) {
                $this->simpanBerkas($peserta, $user->id, $berkasTranskrip, JenisDokumenEnum::Transkrip, 'Transkrip Nilai');
            }

            if ($berkasKeteranganMK) {
                $this->simpanBerkas($peserta, $user->id, $berkasKeteranganMK, JenisDokumenEnum::KeteranganMataKuliah, 'Dokumen Keterangan Mata Kuliah');
            }

            return $peserta;
        });
    }

    private function simpanBerkas(
        Peserta $peserta,
        int $userId,
        UploadedFile $file,
        JenisDokumenEnum $jenis,
        string $nama,
    ): void {
        $path = $file->store('dokumen/' . $peserta->id, 'local');

        DokumenBukti::create([
            'peserta_id'          => $peserta->id,
            'jenis_dokumen'       => $jenis,
            'nama_dokumen'        => $nama,
            'berkas'              => $path,
            'uploaded_by_user_id' => $userId,
        ]);
    }
}
