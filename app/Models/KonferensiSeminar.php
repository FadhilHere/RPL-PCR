<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KonferensiSeminar extends Model
{
    protected $table = 'konferensi_seminar';

    protected $fillable = [
        'peserta_id',
        'tahun',
        'judul_kegiatan',
        'penyelenggara',
        'peran',
    ];

    // --- Belongs To ---

    public function peserta(): BelongsTo
    {
        return $this->belongsTo(Peserta::class);
    }
}
