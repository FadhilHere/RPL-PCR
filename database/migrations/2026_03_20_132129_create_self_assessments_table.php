<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asesmen_mandiri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rpl_mata_kuliah_id')->constrained('rpl_mata_kuliah')->cascadeOnDelete();
            $table->foreignId('cpk_id')->constrained('cpmk')->cascadeOnDelete();
            $table->unsignedTinyInteger('penilaian_diri')->comment('1=Kurang Sekali, 2=Kurang, 3=Cukup, 4=Baik, 5=Sangat Baik');
            $table->timestamps();

            $table->unique(['rpl_mata_kuliah_id', 'cpk_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asesmen_mandiri');
    }
};
