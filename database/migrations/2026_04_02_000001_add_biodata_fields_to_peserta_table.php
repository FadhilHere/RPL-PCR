<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peserta', function (Blueprint $table) {
            $table->string('agama', 50)->nullable()->after('jenis_kelamin');
            $table->string('golongan_pangkat', 100)->nullable()->after('agama');
            $table->string('instansi', 255)->nullable()->after('golongan_pangkat');
            $table->string('pekerjaan', 255)->nullable()->after('instansi');
            $table->string('telepon_faks', 20)->nullable()->after('telepon');
            $table->boolean('profil_lengkap')->default(false)->after('tanggal_pengunduran_diri');
        });
    }

    public function down(): void
    {
        Schema::table('peserta', function (Blueprint $table) {
            $table->dropColumn([
                'agama',
                'golongan_pangkat',
                'instansi',
                'pekerjaan',
                'telepon_faks',
                'profil_lengkap',
            ]);
        });
    }
};
