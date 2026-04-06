<?php

namespace App\Models;

use App\Enums\JenisDokumenEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DokumenBukti extends Model
{
    protected $table = 'dokumen_bukti';

    protected $fillable = [
        'peserta_id',
        'jenis_dokumen',
        'nama_dokumen',
        'berkas',
        'keterangan',
        'uploaded_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'jenis_dokumen' => JenisDokumenEnum::class,
        ];
    }

    // --- Belongs To ---

    public function peserta(): BelongsTo
    {
        return $this->belongsTo(Peserta::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
