<?php

namespace App\Models;

use App\Enums\JenisRplEnum;
use App\Enums\SemesterEnum;
use App\Enums\StatusPermohonanEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PermohonanRpl extends Model
{
    protected $table = 'permohonan_rpl';

    protected $fillable = [
        'peserta_id',
        'program_studi_id',
        'nomor_permohonan',
        'status',
        'catatan_admin',
        'tanggal_pengajuan',
        'pembayaran_terverifikasi',
        'tanggal_verifikasi_pembayaran',
        'admin_verifikator_id',
        'tahun_ajaran_id',
        'semester',
        'jenis_rpl',
        'asesmen_submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pengajuan'            => 'datetime',
            'tanggal_verifikasi_pembayaran' => 'datetime',
            'asesmen_submitted_at'          => 'datetime',
            'status'                        => StatusPermohonanEnum::class,
            'semester'                      => SemesterEnum::class,
            'jenis_rpl'                    => JenisRplEnum::class,
            'pembayaran_terverifikasi'      => 'boolean',
        ];
    }

    // --- Belongs To ---

    public function peserta(): BelongsTo
    {
        return $this->belongsTo(Peserta::class);
    }

    public function programStudi(): BelongsTo
    {
        return $this->belongsTo(ProgramStudi::class);
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    public function adminVerifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_verifikator_id');
    }

    // --- Has Many ---

    public function rplMataKuliah(): HasMany
    {
        return $this->hasMany(RplMataKuliah::class);
    }

    public function verifikasiBersama(): HasMany
    {
        return $this->hasMany(VerifikasiBersama::class);
    }

    // --- Has One ---

    public function skRekognisi(): HasOne
    {
        return $this->hasOne(SkRekognisi::class);
    }

    // --- Belongs To Many ---

    public function asesor(): BelongsToMany
    {
        return $this->belongsToMany(Asesor::class, 'asesor_permohonan');
    }
}
