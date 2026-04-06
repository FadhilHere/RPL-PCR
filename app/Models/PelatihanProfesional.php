<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PelatihanProfesional extends Model
{
    protected $table = 'pelatihan_profesional';

    protected $fillable = [
        'peserta_id',
        'tahun',
        'jenis_pelatihan',
        'penyelenggara',
        'jangka_waktu',
    ];

    // --- Belongs To ---

    public function peserta(): BelongsTo
    {
        return $this->belongsTo(Peserta::class);
    }
}
