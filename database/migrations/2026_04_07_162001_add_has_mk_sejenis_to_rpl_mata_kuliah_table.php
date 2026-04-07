<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rpl_mata_kuliah', function (Blueprint $table) {
            $table->boolean('has_mk_sejenis')->default(false)->after('catatan_asesor');
            $table->string('nilai_transfer')->nullable()->after('has_mk_sejenis'); // A, AB, B, BC, C, D, E
        });
    }

    public function down(): void
    {
        Schema::table('rpl_mata_kuliah', function (Blueprint $table) {
            $table->dropColumn(['has_mk_sejenis', 'nilai_transfer']);
        });
    }
};
