<?php

namespace App\Models;

use App\Enums\StatusVerifikasiEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerifikasiBersama extends Model
{
    protected $table = 'verifikasi_bersama';

    protected $fillable = [
        'permohonan_rpl_id',
        'asesor_id',
        'jadwal',
        'status',
        'catatan',
        'catatan_hasil',
        'berkas',
    ];

    protected function casts(): array
    {
        return [
            'jadwal'  => 'datetime',
            'status'  => StatusVerifikasiEnum::class,
        ];
    }

    public function permohonanRpl(): BelongsTo
    {
        return $this->belongsTo(PermohonanRpl::class);
    }

    public function asesor(): BelongsTo
    {
        return $this->belongsTo(Asesor::class);
    }
}
