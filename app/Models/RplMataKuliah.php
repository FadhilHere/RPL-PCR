<?php

namespace App\Models;

use App\Enums\JenisRplEnum;
use App\Enums\StatusRplMataKuliahEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RplMataKuliah extends Model
{
    protected $table = 'rpl_mata_kuliah';

    protected $fillable = [
        'permohonan_rpl_id',
        'mata_kuliah_id',
        'jenis_rpl',
        'asesor_id',
        'status',
        'nilai_akhir',
        'sks_diakui',
        'catatan_asesor',
    ];

    protected function casts(): array
    {
        return [
            'status'    => StatusRplMataKuliahEnum::class,
            'jenis_rpl' => JenisRplEnum::class,
        ];
    }

    // --- Belongs To ---

    public function permohonanRpl(): BelongsTo
    {
        return $this->belongsTo(PermohonanRpl::class);
    }

    public function mataKuliah(): BelongsTo
    {
        return $this->belongsTo(MataKuliah::class);
    }

    public function asesor(): BelongsTo
    {
        return $this->belongsTo(Asesor::class);
    }

    // --- Has Many ---

    public function asesmenMandiri(): HasMany
    {
        return $this->hasMany(AsesmenMandiri::class);
    }
}
