<?php

namespace App\Actions\Admin;

use App\Enums\RoleEnum;
use App\Models\Asesor;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class TambahAsesorAction
{
    public function execute(
        string $nama,
        string $email,
        string $password,
        ?string $nidn,
        string $bidangKeahlian,
        bool $sudahPelatihan,
        array $prodiIds = [],
    ): Asesor {
        return DB::transaction(function () use ($nama, $email, $password, $nidn, $bidangKeahlian, $sudahPelatihan, $prodiIds) {
            $user = User::create([
                'nama'     => $nama,
                'email'    => $email,
                'password' => Hash::make($password),
                'role'     => RoleEnum::Asesor,
                'aktif'    => true,
            ]);

            $role = Role::firstOrCreate(['name' => 'asesor', 'guard_name' => 'web']);
            $user->assignRole($role);

            $asesor = Asesor::create([
                'user_id'             => $user->id,
                'nidn'                => $nidn ?: null,
                'bidang_keahlian'     => $bidangKeahlian,
                'sudah_pelatihan_rpl' => $sudahPelatihan,
            ]);

            if ($prodiIds) {
                $asesor->programStudi()->sync($prodiIds);
            }

            return $asesor;
        });
    }
}
