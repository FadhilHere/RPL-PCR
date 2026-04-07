<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matkul_lampau', function (Blueprint $table) {
            // Nilai huruf dari transkrip peserta di PT asal (A, AB, B, BC, C, D, E)
            $table->string('nilai_huruf', 5)->nullable()->after('sks');
        });
    }

    public function down(): void
    {
        Schema::table('matkul_lampau', function (Blueprint $table) {
            $table->dropColumn('nilai_huruf');
        });
    }
};
