<?php

namespace App\Models;

use App\Enums\JenisRplEnum;
use App\Enums\SemesterEnum;
use App\Enums\StatusPermohonanEnum;
use App\Enums\StatusRplMataKuliahEnum;
use Illuminate\Database\Eloquent\Builder;
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
        'dirilis_pada',
        'dirilis_oleh_user_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pengajuan'            => 'datetime',
            'tanggal_verifikasi_pembayaran' => 'datetime',
            'asesmen_submitted_at'          => 'datetime',
            'dirilis_pada'                  => 'datetime',
            'status'                        => StatusPermohonanEnum::class,
            'semester'                      => SemesterEnum::class,
            'jenis_rpl'                    => JenisRplEnum::class,
            'pembayaran_terverifikasi'      => 'boolean',
        ];
    }

    // --- Scopes ---

    public function scopeSiapDirilis(Builder $query): void
    {
        $query->whereIn('status', [StatusPermohonanEnum::Disetujui, StatusPermohonanEnum::Ditolak])
              ->whereNull('dirilis_pada');
    }

    // --- Helpers ---

    public function sudahDirilis(): bool
    {
        return $this->dirilis_pada !== null;
    }

    /**
     * Status yang ditampilkan ke peserta. Jika sudah finalized (Disetujui/Ditolak)
     * tapi belum dirilis admin, tampilkan status sebelumnya agar hasil tidak bocor.
     */
    public function statusUntukPeserta(): StatusPermohonanEnum
    {
        if (! in_array($this->status, [StatusPermohonanEnum::Disetujui, StatusPermohonanEnum::Ditolak])) {
            return $this->status;
        }
        if ($this->sudahDirilis()) {
            return $this->status;
        }
        return $this->jenis_rpl === JenisRplEnum::RplI
            ? StatusPermohonanEnum::Verifikasi
            : StatusPermohonanEnum::Asesmen;
    }

    public function hitungStatusByAturan(): StatusPermohonanEnum
    {
        $sksDiakui = $this->rplMataKuliah
            ->where('status', StatusRplMataKuliahEnum::Diakui)
            ->sum(fn ($mk) => $mk->mataKuliah->sks ?? 0);

        $totalSks = $this->programStudi->total_sks ?? 0;
        $batasMin = $totalSks * 0.50;

        return ($totalSks > 0 && $sksDiakui >= $batasMin)
            ? StatusPermohonanEnum::Disetujui
            : StatusPermohonanEnum::Ditolak;
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

    public function dirilisOlehUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dirilis_oleh_user_id');
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
