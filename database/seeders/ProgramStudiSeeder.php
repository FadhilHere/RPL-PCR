<?php

namespace Database\Seeders;

use App\Models\Cpmk;
use App\Models\MataKuliah;
use App\Models\ProgramStudi;
use Illuminate\Database\Seeder;

class ProgramStudiSeeder extends Seeder
{
    public function run(): void
    {
        $prodis = [
            ['kode_sheet' => 'PSTRM',  'kode' => 'TRM',  'nama' => 'Teknik Rekayasa Mekatronika',             'jenjang' => 'D4', 'total_sks' => 144, 'bidang' => 'JTIN'],
            ['kode_sheet' => 'PSTRK',  'kode' => 'TRK',  'nama' => 'Teknik Rekayasa Komputer',                'jenjang' => 'D4', 'total_sks' => 144, 'bidang' => 'JTI'],
            ['kode_sheet' => 'PSTL',   'kode' => 'TL',   'nama' => 'Teknik Listrik',                          'jenjang' => 'D4', 'total_sks' => 144, 'bidang' => 'JTIN'],
            ['kode_sheet' => 'PSTET',  'kode' => 'TET',  'nama' => 'Teknik Elektronika Telekomunikasi',       'jenjang' => 'D4', 'total_sks' => 144, 'bidang' => 'JTIN'],
            ['kode_sheet' => 'PSAKTP', 'kode' => 'AKTP', 'nama' => 'Akuntansi Perpajakan',                   'jenjang' => 'D4', 'total_sks' => 144, 'bidang' => 'JBK'],
            ['kode_sheet' => 'PSSI',   'kode' => 'SI',   'nama' => 'Sistem Informasi',                        'jenjang' => 'D4', 'total_sks' => 144, 'bidang' => 'JTI'],
            ['kode_sheet' => 'PSTI',   'kode' => 'TI',   'nama' => 'Teknik Informatika',                      'jenjang' => 'D4', 'total_sks' => 144, 'bidang' => 'JTI'],
            ['kode_sheet' => 'PSTRSE', 'kode' => 'TRSE', 'nama' => 'Teknik Rekayasa Sistem Energi',           'jenjang' => 'D4', 'total_sks' => 144, 'bidang' => 'JTIN'],
            ['kode_sheet' => 'PSMS',   'kode' => 'MS',   'nama' => 'Teknik Mesin',                            'jenjang' => 'D4', 'total_sks' => 144, 'bidang' => 'JTIN'],
            ['kode_sheet' => 'PSTRJT', 'kode' => 'TRJT', 'nama' => 'Teknik Rekayasa Jaringan Telekomunikasi', 'jenjang' => 'D4', 'total_sks' => 144, 'bidang' => 'JTIN'],
            ['kode_sheet' => 'MTTK',   'kode' => 'MTTK', 'nama' => 'Magister Terapan Teknik Komputer',        'jenjang' => 'S2', 'total_sks' => 48,  'bidang' => 'JTI'],
        ];

        foreach ($prodis as $data) {
            ProgramStudi::updateOrCreate(['kode_sheet' => $data['kode_sheet']], $data);
        }

        // Sample MK + CPMK untuk prodi TI (Teknik Informatika)
        $ti = ProgramStudi::where('kode', 'TI')->first();
        if (!$ti) {
            return;
        }

        $mataKuliahData = [
            [
                'kode' => 'TI201',
                'nama' => 'Algoritma dan Pemrograman',
                'sks' => 3,
                'semester' => 1,
                'deskripsi' => 'Pengenalan konsep algoritma, tipe data, struktur kontrol, dan implementasi program dalam bahasa pemrograman terstruktur.',
                'bisa_rpl' => true,
                'cpmk' => [
                    'Mampu memahami konsep dasar algoritma, variabel, dan tipe data.',
                    'Mampu menulis kode program dengan bahasa pemrograman terstruktur.',
                    'Mampu mengimplementasikan struktur kontrol seleksi dan perulangan.',
                ],
            ],
            [
                'kode' => 'TI202',
                'nama' => 'Basis Data',
                'sks' => 3,
                'semester' => 2,
                'deskripsi' => 'Perancangan model data relasional, penulisan query SQL, dan implementasi normalisasi database.',
                'bisa_rpl' => true,
                'cpmk' => [
                    'Mampu merancang model data menggunakan Entity Relationship Diagram (ERD).',
                    'Mampu menulis query SQL dasar (SELECT, INSERT, UPDATE, DELETE).',
                    'Mampu mengimplementasikan normalisasi database hingga 3NF.',
                ],
            ],
            [
                'kode' => 'TI301',
                'nama' => 'Rekayasa Perangkat Lunak',
                'sks' => 3,
                'semester' => 3,
                'deskripsi' => 'Metodologi pengembangan perangkat lunak, manajemen proyek, dan pemodelan sistem menggunakan UML.',
                'bisa_rpl' => true,
                'cpmk' => [
                    'Mampu memahami siklus hidup pengembangan perangkat lunak (SDLC).',
                    'Mampu membuat diagram UML (use case, class diagram, sequence diagram).',
                    'Mampu menulis dokumen spesifikasi kebutuhan perangkat lunak.',
                    'Mampu menerapkan metodologi Agile/Scrum dalam pengembangan proyek.',
                ],
            ],
        ];

        foreach ($mataKuliahData as $mkData) {
            $cpmkList = $mkData['cpmk'];
            unset($mkData['cpmk']);

            $mk = MataKuliah::firstOrCreate(
                ['program_studi_id' => $ti->id, 'kode' => $mkData['kode']],
                array_merge($mkData, ['program_studi_id' => $ti->id])
            );

            foreach ($cpmkList as $urutan => $deskripsi) {
                Cpmk::firstOrCreate(
                    ['mata_kuliah_id' => $mk->id, 'urutan' => $urutan + 1],
                    ['deskripsi' => $deskripsi]
                );
            }
        }
    }
}
