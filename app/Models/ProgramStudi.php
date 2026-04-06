<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProgramStudi extends Model
{
    protected $table = 'program_studi';

    protected $fillable = [
        'kode',
        'nama',
        'jenjang',
        'total_sks',
        'aktif',
        'bidang',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    // --- Has Many ---

    public function mataKuliah(): HasMany
    {
        return $this->hasMany(MataKuliah::class);
    }

    // --- Belongs To Many ---

    public function asesors(): BelongsToMany
    {
        return $this->belongsToMany(Asesor::class, 'asesor_program_studi');
    }
}
