<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pertanyaan extends Model
{
    protected $table = 'pertanyaan';

    protected $fillable = [
        'mata_kuliah_id',
        'pertanyaan',
        'urutan',
    ];

    // --- Belongs To ---

    public function mataKuliah(): BelongsTo
    {
        return $this->belongsTo(MataKuliah::class);
    }

    // --- Has Many ---

    public function asesmenMandiri(): HasMany
    {
        return $this->hasMany(AsesmenMandiri::class);
    }
}
