<?php

namespace App\Actions\Admin;

use App\Enums\RoleEnum;
use App\Models\Peserta;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class TambahPesertaAction
{
    public function execute(
        string $nama,
        string $email,
        string $password,
        ?string $nik,
        ?string $telepon,
        ?string $institusiAsal,
    ): Peserta {
        return DB::transaction(function () use ($nama, $email, $password, $nik, $telepon, $institusiAsal) {
            $user = User::create([
                'nama'     => $nama,
                'email'    => $email,
                'password' => Hash::make($password),
                'role'     => RoleEnum::Peserta,
                'aktif'    => true,
            ]);

            $role = Role::firstOrCreate(['name' => 'peserta', 'guard_name' => 'web']);
            $user->assignRole($role);

            return Peserta::create([
                'user_id'        => $user->id,
                'nik'            => $nik ?: null,
                'telepon'        => $telepon ?: null,
                'institusi_asal' => $institusiAsal ?: null,
            ]);
        });
    }
}
