<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganisasiProfesi extends Model
{
    protected $table = 'organisasi_profesi';

    protected $fillable = [
        'peserta_id',
        'tahun',
        'nama_organisasi',
        'jabatan',
    ];

    // --- Belongs To ---

    public function peserta(): BelongsTo
    {
        return $this->belongsTo(Peserta::class);
    }
}
