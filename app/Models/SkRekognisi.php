<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkRekognisi extends Model
{
    protected $table = 'sk_rekognisi';

    protected $fillable = [
        'permohonan_rpl_id',
        'nomor_sk',
        'tanggal_sk',
        'berkas',
        'diterbitkan_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_sk' => 'date',
        ];
    }

    public function permohonanRpl(): BelongsTo
    {
        return $this->belongsTo(PermohonanRpl::class);
    }

    public function diterbitkanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diterbitkan_oleh');
    }
}
