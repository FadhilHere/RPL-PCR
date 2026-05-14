<?php

namespace App\Models;

use App\Enums\NilaiTranskripEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatkulLampau extends Model
{
    protected $table = 'matkul_lampau';

    protected $fillable = [
        'rpl_mata_kuliah_id',
        'kode_mk',
        'nama_mk',
        'sks',
        'nilai_huruf',
        'kode_mk_asesor',
        'nama_mk_asesor',
        'sks_asesor',
        'nilai_huruf_asesor',
        'catatan_asesor',
    ];

    protected function casts(): array
    {
        return [
            'nilai_huruf'        => NilaiTranskripEnum::class,
            'nilai_huruf_asesor' => NilaiTranskripEnum::class,
        ];
    }

    public function getKodeMkFinalAttribute(): ?string
    {
        return $this->kode_mk_asesor ?? $this->kode_mk;
    }

    public function getNamaMkFinalAttribute(): ?string
    {
        return $this->nama_mk_asesor ?? $this->nama_mk;
    }

    public function getSksFinalAttribute(): ?int
    {
        return $this->sks_asesor ?? $this->sks;
    }

    public function getNilaiHurufFinalAttribute(): ?NilaiTranskripEnum
    {
        return $this->nilai_huruf_asesor ?? $this->nilai_huruf;
    }

    public function isDitambahAsesor(): bool
    {
        return is_null($this->kode_mk);
    }

    public function isOverridden(string $field): bool
    {
        return ! is_null($this->{"{$field}_asesor"});
    }

    public function rplMataKuliah(): BelongsTo
    {
        return $this->belongsTo(RplMataKuliah::class);
    }
}
