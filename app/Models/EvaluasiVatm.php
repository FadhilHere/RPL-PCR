<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluasiVatm extends Model
{
    protected $table = 'evaluasi_vatm';

    protected $fillable = [
        'asesmen_mandiri_id',
        'asesor_id',
        'valid',
        'autentik',
        'terkini',
        'memadai',
        'catatan',
        'dievaluasi_pada',
    ];

    protected function casts(): array
    {
        return [
            'valid'          => 'boolean',
            'autentik'       => 'boolean',
            'terkini'        => 'boolean',
            'memadai'        => 'boolean',
            'dievaluasi_pada' => 'datetime',
        ];
    }

    // --- Belongs To ---

    public function asesmenMandiri(): BelongsTo
    {
        return $this->belongsTo(AsesmenMandiri::class);
    }

    public function asesor(): BelongsTo
    {
        return $this->belongsTo(Asesor::class);
    }
}
