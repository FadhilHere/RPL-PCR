<?php

namespace App\Actions\Admin;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class TambahAdminAction
{
    public function execute(string $nama, string $email, string $password): User
    {
        $user = User::create([
            'nama'     => $nama,
            'email'    => $email,
            'password' => Hash::make($password),
            'role'     => RoleEnum::Admin,
            'aktif'    => true,
        ]);

        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user->assignRole($role);

        return $user;
    }
}
