<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('program_studi', function (Blueprint $table) {
            $table->string('bidang', 20)->nullable()->after('aktif')
                  ->comment('Kode jurusan/bidang: JTI, JTIN, JBK, dst');
        });
    }

    public function down(): void
    {
        Schema::table('program_studi', function (Blueprint $table) {
            $table->dropColumn('bidang');
        });
    }
};
