<?php

namespace App\Actions\Admin;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class TambahAdminAction
{
    public function execute(string $nama, string $email, string $password, RoleEnum $roleEnum = RoleEnum::Admin): User
    {
        $user = User::create([
            'nama'     => $nama,
            'email'    => $email,
            'password' => Hash::make($password),
            'role'     => $roleEnum,
            'aktif'    => true,
        ]);

        $role = Role::firstOrCreate(['name' => $roleEnum->value, 'guard_name' => 'web']);
        $user->assignRole($role);

        return $user;
    }
}
