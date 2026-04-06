<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Penghargaan extends Model
{
    protected $table = 'penghargaan';

    protected $fillable = [
        'peserta_id',
        'tahun',
        'bentuk_penghargaan',
        'pemberi',
    ];

    // --- Belongs To ---

    public function peserta(): BelongsTo
    {
        return $this->belongsTo(Peserta::class);
    }
}
