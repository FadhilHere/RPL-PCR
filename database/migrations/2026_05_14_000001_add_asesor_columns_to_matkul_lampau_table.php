<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matkul_lampau', function (Blueprint $table) {
            $table->string('kode_mk_asesor', 20)->nullable()->after('kode_mk');
            $table->string('nama_mk_asesor', 255)->nullable()->after('nama_mk');
            $table->tinyInteger('sks_asesor')->nullable()->after('sks');
            $table->string('nilai_huruf_asesor', 5)->nullable()->after('nilai_huruf');
        });
    }

    public function down(): void
    {
        Schema::table('matkul_lampau', function (Blueprint $table) {
            $table->dropColumn(['kode_mk_asesor', 'nama_mk_asesor', 'sks_asesor', 'nilai_huruf_asesor']);
        });
    }
};
