<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatPendidikan extends Model
{
    protected $table = 'riwayat_pendidikan';

    protected $fillable = [
        'peserta_id',
        'nama_sekolah',
        'tahun_lulus',
        'jurusan',
    ];

    // --- Belongs To ---

    public function peserta(): BelongsTo
    {
        return $this->belongsTo(Peserta::class);
    }
}
