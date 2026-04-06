<?php

namespace App\Actions\Peserta;

use App\Models\KonferensiSeminar;
use App\Models\OrganisasiProfesi;
use App\Models\Penghargaan;
use App\Models\PelatihanProfesional;
use App\Models\Peserta;
use App\Models\RiwayatPendidikan;
use Illuminate\Support\Facades\DB;

class LengkapiProfilAction
{
    public function execute(Peserta $peserta, array $biodata, array $seksi): void
    {
        DB::transaction(function () use ($peserta, $biodata, $seksi) {

            // 1. Update biodata utama
            $peserta->update([
                'nik'             => $biodata['nik'] ?? null,
                'telepon'         => $biodata['telepon'] ?? null,
                'telepon_faks'    => $biodata['teleponFaks'] ?? null,
                'alamat'          => $biodata['alamat'] ?? null,
                'kota'            => $biodata['kota'] ?? null,
                'provinsi'        => $biodata['provinsi'] ?? null,
                'kode_pos'        => $biodata['kodePos'] ?? null,
                'tempat_lahir'    => $biodata['tempatLahir'] ?? null,
                'tanggal_lahir'   => $biodata['tanggalLahir'] ?? null,
                'jenis_kelamin'   => $biodata['jenisKelamin'] ?? null,
                'agama'           => $biodata['agama'] ?? null,
                'golongan_pangkat' => $biodata['golonganPangkat'] ?? null,
                'instansi'        => $biodata['instansi'] ?? null,
                'pekerjaan'       => $biodata['pekerjaan'] ?? null,
                'profil_lengkap'  => true,
            ]);

            // 2. Riwayat pendidikan — replace semua
            $peserta->riwayatPendidikan()->delete();
            foreach (($seksi['riwayatPendidikan'] ?? []) as $row) {
                if (empty($row['namaSekolah'])) continue;
                RiwayatPendidikan::create([
                    'peserta_id'  => $peserta->id,
                    'nama_sekolah' => $row['namaSekolah'],
                    'tahun_lulus' => $row['tahunLulus'] ?? null,
                    'jurusan'     => $row['jurusan'] ?? null,
                ]);
            }

            // 3. Pelatihan profesional — replace semua
            $peserta->pelatihanProfesional()->delete();
            foreach (($seksi['pelatihan'] ?? []) as $row) {
                if (empty($row['jenisPelatihan'])) continue;
                PelatihanProfesional::create([
                    'peserta_id'      => $peserta->id,
                    'tahun'           => $row['tahun'] ?? '',
                    'jenis_pelatihan' => $row['jenisPelatihan'],
                    'penyelenggara'   => $row['penyelenggara'] ?? '',
                    'jangka_waktu'    => $row['jangkaWaktu'] ?? null,
                ]);
            }

            // 4. Konferensi / seminar — replace semua
            $peserta->konferensiSeminar()->delete();
            foreach (($seksi['konferensi'] ?? []) as $row) {
                if (empty($row['judulKegiatan'])) continue;
                KonferensiSeminar::create([
                    'peserta_id'     => $peserta->id,
                    'tahun'          => $row['tahun'] ?? '',
                    'judul_kegiatan' => $row['judulKegiatan'],
                    'penyelenggara'  => $row['penyelenggara'] ?? '',
                    'peran'          => $row['peran'] ?? null,
                ]);
            }

            // 5. Penghargaan — replace semua
            $peserta->penghargaan()->delete();
            foreach (($seksi['penghargaan'] ?? []) as $row) {
                if (empty($row['bentukPenghargaan'])) continue;
                Penghargaan::create([
                    'peserta_id'         => $peserta->id,
                    'tahun'              => $row['tahun'] ?? '',
                    'bentuk_penghargaan' => $row['bentukPenghargaan'],
                    'pemberi'            => $row['pemberi'] ?? '',
                ]);
            }

            // 6. Organisasi profesi — replace semua
            $peserta->organisasiProfesi()->delete();
            foreach (($seksi['organisasi'] ?? []) as $row) {
                if (empty($row['namaOrganisasi'])) continue;
                OrganisasiProfesi::create([
                    'peserta_id'      => $peserta->id,
                    'tahun'           => $row['tahun'] ?? '',
                    'nama_organisasi' => $row['namaOrganisasi'],
                    'jabatan'         => $row['jabatan'] ?? null,
                ]);
            }
        });
    }
}
