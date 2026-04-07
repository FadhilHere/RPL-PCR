<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BeritaAcara extends Model
{
    protected $table = 'berita_acara';

    protected $fillable = [
        'asesor_id',
        'tahun_ajaran_id',
        'penandatangan_kiri_id',
        'penandatangan_kanan_id',
        'tanggal_asesmen',
        'jumlah_peserta',
        'jumlah_hadir',
        'jumlah_tidak_hadir',
        'is_locked',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_asesmen' => 'date',
            'is_locked'       => 'boolean',
            'generated_at'    => 'datetime',
        ];
    }

    public function asesor(): BelongsTo
    {
        return $this->belongsTo(Asesor::class);
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    public function penandatanganKiri(): BelongsTo
    {
        return $this->belongsTo(Penandatangan::class, 'penandatangan_kiri_id');
    }

    public function penandatanganKanan(): BelongsTo
    {
        return $this->belongsTo(Penandatangan::class, 'penandatangan_kanan_id');
    }

    public function peserta(): HasMany
    {
        return $this->hasMany(BeritaAcaraPeserta::class);
    }
}
