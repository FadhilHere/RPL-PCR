<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah 'diproses' (admin assign MK) dan 'verifikasi' (peserta submit asesmen) ke enum
        DB::statement("ALTER TABLE permohonan_rpl MODIFY COLUMN status ENUM('draf','diajukan','diproses','verifikasi','dalam_review','disetujui','ditolak') DEFAULT 'draf'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE permohonan_rpl MODIFY COLUMN status ENUM('draf','diajukan','dalam_review','disetujui','ditolak') DEFAULT 'draf'");
    }
};
