<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `dokumen_bukti` MODIFY COLUMN `jenis_dokumen` ENUM(
            'cv',
            'ijazah',
            'transkrip',
            'keterangan_mata_kuliah',
            'sertifikat',
            'surat_keterangan',
            'logbook',
            'karya_monumental',
            'keanggotaan_profesi',
            'dukungan_asosiasi',
            'bukti_pengalaman_kerja',
            'bukti_keahlian',
            'pernyataan_sejawat',
            'pelatihan',
            'workshop_seminar',
            'karya_penghargaan',
            'lainnya'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `dokumen_bukti` MODIFY COLUMN `jenis_dokumen` ENUM(
            'ijazah', 'transkrip', 'rps_silabus', 'sertifikat',
            'logbook', 'surat_keterangan', 'cv', 'penilaian_kinerja',
            'karya_monumental', 'lainnya'
        ) NOT NULL");
    }
};
