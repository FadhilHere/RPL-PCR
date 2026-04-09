<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Alter penandatangan.posisi enum to include 'wadir'
        DB::statement("ALTER TABLE penandatangan MODIFY COLUMN posisi ENUM('kiri','kanan','wadir') NOT NULL");

        // 2. Add ketua prodi columns to program_studi
        Schema::table('program_studi', function (Blueprint $table) {
            $table->string('ketua_nama')->nullable()->after('aktif');
            $table->string('ketua_nip', 30)->nullable()->after('ketua_nama');
            $table->string('ketua_jabatan')->nullable()->default('Ketua Program Studi')->after('ketua_nip');
            $table->string('ketua_tanda_tangan')->nullable()->after('ketua_jabatan');
        });
    }

    public function down(): void
    {
        Schema::table('program_studi', function (Blueprint $table) {
            $table->dropColumn(['ketua_nama', 'ketua_nip', 'ketua_jabatan', 'ketua_tanda_tangan']);
        });

        DB::statement("ALTER TABLE penandatangan MODIFY COLUMN posisi ENUM('kiri','kanan') NOT NULL");
    }
};
