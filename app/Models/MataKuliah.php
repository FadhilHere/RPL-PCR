<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MataKuliah extends Model
{
    protected $table = 'mata_kuliah';

    protected $fillable = [
        'program_studi_id',
        'kode',
        'nama',
        'sks',
        'semester',
        'deskripsi',
        'cpl',
        'bisa_rpl',
    ];

    protected function casts(): array
    {
        return [
            'bisa_rpl' => 'boolean',
        ];
    }

    // --- Belongs To ---

    public function programStudi(): BelongsTo
    {
        return $this->belongsTo(ProgramStudi::class);
    }

    // --- Has Many ---

    public function cpmk(): HasMany
    {
        return $this->hasMany(Cpmk::class)->orderBy('urutan');
    }

    public function pertanyaan(): HasMany
    {
        return $this->hasMany(Pertanyaan::class)->orderBy('urutan');
    }
}
