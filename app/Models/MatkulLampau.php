<?php

namespace App\Models;

use App\Enums\NilaiHurufEnum;
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
        'catatan_asesor',
    ];

    protected function casts(): array
    {
        return [
            'nilai_huruf' => NilaiHurufEnum::class,
        ];
    }

    public function rplMataKuliah(): BelongsTo
    {
        return $this->belongsTo(RplMataKuliah::class);
    }
}
