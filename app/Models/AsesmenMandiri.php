<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AsesmenMandiri extends Model
{
    protected $table = 'asesmen_mandiri';

    protected $fillable = [
        'rpl_mata_kuliah_id',
        'pertanyaan_id',
        'penilaian_diri',
        'referensi_berkas',
    ];

    protected function casts(): array
    {
        return [
            'referensi_berkas' => 'array',
        ];
    }

    // --- Belongs To ---

    public function rplMataKuliah(): BelongsTo
    {
        return $this->belongsTo(RplMataKuliah::class);
    }

    public function pertanyaan(): BelongsTo
    {
        return $this->belongsTo(Pertanyaan::class);
    }

    // --- Has One ---

    public function evaluasiVatm(): HasOne
    {
        return $this->hasOne(EvaluasiVatm::class);
    }

    public function nilaiAsesor(): HasOne
    {
        return $this->hasOne(NilaiAsesor::class);
    }

}
