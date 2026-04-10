<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('peserta', function (Blueprint $table) {
            $table->string('program_studi_asal', 255)->nullable()->after('institusi_asal');
            $table->string('peringkat_akreditasi_asal', 100)->nullable()->after('program_studi_asal');
        });
    }

    public function down(): void
    {
        Schema::table('peserta', function (Blueprint $table) {
            $table->dropColumn(['program_studi_asal', 'peringkat_akreditasi_asal']);
        });
    }
};
