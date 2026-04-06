<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cpmk extends Model
{
    protected $table = 'cpmk';

    protected $fillable = [
        'mata_kuliah_id',
        'deskripsi',
        'urutan',
    ];

    // --- Belongs To ---

    public function mataKuliah(): BelongsTo
    {
        return $this->belongsTo(MataKuliah::class);
    }
}
