<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TahunAjaran extends Model
{
    protected $table = 'tahun_ajaran';

    protected $fillable = [
        'nama',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    // --- Has Many ---

    public function permohonanRpl(): HasMany
    {
        return $this->hasMany(PermohonanRpl::class);
    }

    // --- Scope ---

    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }
}
