<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah 'asesmen' ke enum (sementara pertahankan 'dalam_review' untuk backfill)
        DB::statement("ALTER TABLE permohonan_rpl MODIFY COLUMN status ENUM('draf','diajukan','diproses','asesmen','verifikasi','dalam_review','disetujui','ditolak') DEFAULT 'draf'");

        // 2. Backfill record lama: dalam_review → asesmen (RplII) atau verifikasi (RplI)
        DB::statement("UPDATE permohonan_rpl SET status = 'asesmen' WHERE status = 'dalam_review' AND jenis_rpl = 'rpl_ii'");
        DB::statement("UPDATE permohonan_rpl SET status = 'verifikasi' WHERE status = 'dalam_review' AND jenis_rpl = 'rpl_i'");

        // 3. Drop 'dalam_review' dari enum (sudah tidak ada record yang memakainya)
        DB::statement("ALTER TABLE permohonan_rpl MODIFY COLUMN status ENUM('draf','diajukan','diproses','asesmen','verifikasi','disetujui','ditolak') DEFAULT 'draf'");
    }

    public function down(): void
    {
        // Tambah kembali 'dalam_review' ke enum
        DB::statement("ALTER TABLE permohonan_rpl MODIFY COLUMN status ENUM('draf','diajukan','diproses','asesmen','verifikasi','dalam_review','disetujui','ditolak') DEFAULT 'draf'");

        // Reverse backfill: asesmen → dalam_review (RplII), verifikasi → dalam_review (RplI)
        // Catatan: ini lossy karena status 'asesmen'/'verifikasi' baru juga akan ikut, hanya untuk rollback test.
        DB::statement("UPDATE permohonan_rpl SET status = 'dalam_review' WHERE status = 'asesmen'");
        DB::statement("UPDATE permohonan_rpl SET status = 'dalam_review' WHERE status = 'verifikasi' AND jenis_rpl = 'rpl_i'");

        // Hapus 'asesmen' dari enum
        DB::statement("ALTER TABLE permohonan_rpl MODIFY COLUMN status ENUM('draf','diajukan','diproses','verifikasi','dalam_review','disetujui','ditolak') DEFAULT 'draf'");
    }
};
