<?php

namespace App\Models;

use App\Enums\RoleEnum;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'nama',
        'email',
        'password',
        'role',
        'aktif',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'aktif'             => 'boolean',
            'role'              => RoleEnum::class,
        ];
    }

    // --- Has One ---

    public function peserta(): HasOne
    {
        return $this->hasOne(Peserta::class);
    }

    public function asesor(): HasOne
    {
        return $this->hasOne(Asesor::class);
    }
}
