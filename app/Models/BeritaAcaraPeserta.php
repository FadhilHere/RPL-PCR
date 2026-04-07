<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeritaAcaraPeserta extends Model
{
    protected $table = 'berita_acara_peserta';

    protected $fillable = [
        'berita_acara_id',
        'peserta_id',
        'permohonan_rpl_id',
        'hadir',
        'total_sks_diperoleh',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'hadir' => 'boolean',
        ];
    }

    public function beritaAcara(): BelongsTo
    {
        return $this->belongsTo(BeritaAcara::class);
    }

    public function peserta(): BelongsTo
    {
        return $this->belongsTo(Peserta::class);
    }

    public function permohonanRpl(): BelongsTo
    {
        return $this->belongsTo(PermohonanRpl::class);
    }
}
