<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NilaiAsesor extends Model
{
    protected $table = 'nilai_asesor';

    protected $fillable = [
        'asesmen_mandiri_id',
        'asesor_id',
        'nilai',
        'catatan',
        'dinilai_pada',
    ];

    protected function casts(): array
    {
        return [
            'dinilai_pada' => 'datetime',
            'nilai'        => 'integer',
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
