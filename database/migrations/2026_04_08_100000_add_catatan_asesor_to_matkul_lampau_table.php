<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matkul_lampau', function (Blueprint $table) {
            $table->text('catatan_asesor')->nullable()->after('nilai_huruf');
        });
    }

    public function down(): void
    {
        Schema::table('matkul_lampau', function (Blueprint $table) {
            $table->dropColumn('catatan_asesor');
        });
    }
};
