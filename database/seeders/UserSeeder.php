<?php

namespace Database\Seeders;

use App\Models\Asesor;
use App\Models\Peserta;
use App\Models\ProgramStudi;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Buat Spatie roles (jika belum ada)
        $roleAdmin   = Role::firstOrCreate(['name' => 'admin',   'guard_name' => 'web']);
        $roleAsesor  = Role::firstOrCreate(['name' => 'asesor',  'guard_name' => 'web']);
        $rolePeserta = Role::firstOrCreate(['name' => 'peserta', 'guard_name' => 'web']);

        // --- Admin ---
        $adminUser = User::create([
            'nama'              => 'Admin RPL',
            'email'             => 'admin@rpl.pcr.ac.id',
            'password'          => Hash::make('password'),
            'role'              => 'admin',
            'aktif'             => true,
            'email_verified_at' => now(),
        ]);
        $adminUser->assignRole($roleAdmin);

        // --- Asesor ---
        $asesorUser = User::create([
            'nama'              => 'Dr. Budi Santoso, M.T.',
            'email'             => 'budi.santoso@pcr.ac.id',
            'password'          => Hash::make('password'),
            'role'              => 'asesor',
            'aktif'             => true,
            'email_verified_at' => now(),
        ]);
        $asesorUser->assignRole($roleAsesor);

        $asesorRecord = Asesor::create([
            'user_id'               => $asesorUser->id,
            'nidn'                  => '1012108501',
            'bidang_keahlian'       => 'Teknik Informatika',
            'sertifikat_kompetensi' => null,
            'sudah_pelatihan_rpl'   => true,
        ]);

        $tiProdi = ProgramStudi::where('kode', 'TI')->first();
        if ($tiProdi) {
            $asesorRecord->programStudi()->attach($tiProdi->id);
        }

        // --- Peserta ---
        $pesertaUser = User::create([
            'nama'              => 'Siti Rahma Dewi',
            'email'             => 'siti.rahma@gmail.com',
            'password'          => Hash::make('password'),
            'role'              => 'peserta',
            'aktif'             => true,
            'email_verified_at' => now(),
        ]);
        $pesertaUser->assignRole($rolePeserta);

        Peserta::create([
            'user_id'             => $pesertaUser->id,
            'nik'                 => '1471045308990001',
            'telepon'             => '08123456789',
            'alamat'              => 'Jl. Sudirman No. 12, Pekanbaru, Riau',
            'tempat_lahir'        => 'Pekanbaru',
            'tanggal_lahir'       => '1999-08-13',
            'jenis_kelamin'       => 'P',
            'pendidikan_terakhir' => 'SMK Teknik Komputer dan Jaringan',
            'institusi_asal'      => 'SMK Negeri 2 Pekanbaru',
            'tahun_lulus'         => 2018,
            'is_do_pcr'           => false,
        ]);
    }
}
