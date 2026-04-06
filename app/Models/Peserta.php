<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Peserta extends Model
{
    use HasFactory;

    protected $table = 'peserta';

    protected $fillable = [
        'user_id',
        'nik',
        'telepon',
        'telepon_faks',
        'alamat',
        'kota',
        'provinsi',
        'kode_pos',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'agama',
        'golongan_pangkat',
        'instansi',
        'pekerjaan',
        'pendidikan_terakhir',
        'institusi_asal',
        'tahun_lulus',
        'is_do_pcr',
        'tanggal_pengunduran_diri',
        'profil_lengkap',
        'foto',
        'tahun_ajaran_id',
        'semester',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir'            => 'date',
            'tanggal_pengunduran_diri' => 'date',
            'is_do_pcr'                => 'boolean',
            'profil_lengkap'           => 'boolean',
            'semester'                 => \App\Enums\SemesterEnum::class,
        ];
    }

    // --- Belongs To ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    // --- Has Many ---

    public function permohonanRpl(): HasMany
    {
        return $this->hasMany(PermohonanRpl::class);
    }

    public function latestPermohonan(): HasOne
    {
        return $this->hasOne(PermohonanRpl::class)->latestOfMany();
    }

    public function dokumenBukti(): HasMany
    {
        return $this->hasMany(DokumenBukti::class);
    }

    public function riwayatPendidikan(): HasMany
    {
        return $this->hasMany(RiwayatPendidikan::class);
    }

    public function pelatihanProfesional(): HasMany
    {
        return $this->hasMany(PelatihanProfesional::class);
    }

    public function konferensiSeminar(): HasMany
    {
        return $this->hasMany(KonferensiSeminar::class);
    }

    public function penghargaan(): HasMany
    {
        return $this->hasMany(Penghargaan::class);
    }

    public function organisasiProfesi(): HasMany
    {
        return $this->hasMany(OrganisasiProfesi::class);
    }
}
