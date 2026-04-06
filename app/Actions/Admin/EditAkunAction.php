<?php

namespace App\Actions\Admin;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EditAkunAction
{
    public function execute(
        int $userId,
        string $nama,
        string $email,
        string $newPassword,
        // Asesor fields
        ?string $nidn,
        ?string $bidangKeahlian,
        bool $sudahPelatihan,
        array $prodiIds,
    ): void {
        DB::transaction(function () use (
            $userId, $nama, $email, $newPassword,
            $nidn, $bidangKeahlian, $sudahPelatihan, $prodiIds,
        ) {
            $user = User::with(['asesor'])->findOrFail($userId);

            $updateData = ['nama' => $nama, 'email' => $email];
            if ($newPassword !== '') {
                $updateData['password'] = Hash::make($newPassword);
            }
            $user->update($updateData);

            if ($user->role === RoleEnum::Asesor && $user->asesor) {
                $user->asesor->update([
                    'nidn'                => $nidn ?: null,
                    'bidang_keahlian'     => $bidangKeahlian,
                    'sudah_pelatihan_rpl' => $sudahPelatihan,
                ]);
                $user->asesor->programStudi()->sync($prodiIds);
            }
        });
    }
}
