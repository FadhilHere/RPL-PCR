<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peserta', function (Blueprint $table) {
            $table->string('kota', 100)->nullable()->after('alamat');
            $table->string('provinsi', 100)->nullable()->after('kota');
            $table->string('kode_pos', 10)->nullable()->after('provinsi');
        });
    }

    public function down(): void
    {
        Schema::table('peserta', function (Blueprint $table) {
            $table->dropColumn(['kota', 'provinsi', 'kode_pos']);
        });
    }
};
