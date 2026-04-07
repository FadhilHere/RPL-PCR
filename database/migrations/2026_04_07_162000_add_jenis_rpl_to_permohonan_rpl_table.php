<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permohonan_rpl', function (Blueprint $table) {
            $table->string('jenis_rpl')->default('rpl_ii')->after('semester');
        });
    }

    public function down(): void
    {
        Schema::table('permohonan_rpl', function (Blueprint $table) {
            $table->dropColumn('jenis_rpl');
        });
    }
};
