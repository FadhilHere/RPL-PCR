<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Asesor extends Model
{
    use HasFactory;

    protected $table = 'asesor';

    protected $fillable = [
        'user_id',
        'nidn',
        'bidang_keahlian',
        'sertifikat_kompetensi',
        'sudah_pelatihan_rpl',
    ];

    protected function casts(): array
    {
        return [
            'sudah_pelatihan_rpl' => 'boolean',
        ];
    }

    // --- Belongs To ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // --- Belongs To Many ---

    public function programStudi(): BelongsToMany
    {
        return $this->belongsToMany(ProgramStudi::class, 'asesor_program_studi');
    }
}
