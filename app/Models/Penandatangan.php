<?php

namespace App\Models;

use App\Enums\PosisiPenandatanganEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Penandatangan extends Model
{
    protected $table = 'penandatangan';

    protected $fillable = [
        'nama',
        'jabatan',
        'nip',
        'posisi',
        'aktif',
        'urutan',
        'tanda_tangan',
    ];

    protected function casts(): array
    {
        return [
            'posisi' => PosisiPenandatanganEnum::class,
            'aktif'  => 'boolean',
        ];
    }

    public function beritaAcaraKiri(): HasMany
    {
        return $this->hasMany(BeritaAcara::class, 'penandatangan_kiri_id');
    }

    public function beritaAcaraKanan(): HasMany
    {
        return $this->hasMany(BeritaAcara::class, 'penandatangan_kanan_id');
    }
}
