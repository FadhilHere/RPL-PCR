<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Refactor dokumen_bukti: ganti permohonan_rpl_id & asesmen_mandiri_id → peserta_id
        Schema::table('dokumen_bukti', function (Blueprint $table) {
            $table->dropForeign(['asesmen_mandiri_id']);
            $table->dropForeign(['permohonan_rpl_id']);
            $table->dropColumn(['asesmen_mandiri_id', 'permohonan_rpl_id']);

            $table->foreignId('peserta_id')
                ->after('id')
                ->constrained('peserta')
                ->cascadeOnDelete();
        });

        // 2. Tambah referensi_berkas (JSON) ke asesmen_mandiri
        Schema::table('asesmen_mandiri', function (Blueprint $table) {
            $table->json('referensi_berkas')->nullable()->after('penilaian_diri');
        });
    }

    public function down(): void
    {
        Schema::table('asesmen_mandiri', function (Blueprint $table) {
            $table->dropColumn('referensi_berkas');
        });

        Schema::table('dokumen_bukti', function (Blueprint $table) {
            $table->dropForeign(['peserta_id']);
            $table->dropColumn('peserta_id');

            $table->foreignId('permohonan_rpl_id')
                ->after('id')
                ->constrained('permohonan_rpl')
                ->cascadeOnDelete();

            $table->foreignId('asesmen_mandiri_id')
                ->nullable()
                ->after('permohonan_rpl_id')
                ->constrained('asesmen_mandiri')
                ->nullOnDelete();
        });
    }
};
