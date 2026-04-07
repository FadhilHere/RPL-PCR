<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matkul_lampau', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rpl_mata_kuliah_id')->constrained('rpl_mata_kuliah')->cascadeOnDelete();
            $table->string('kode_mk');
            $table->string('nama_mk');
            $table->tinyInteger('sks');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matkul_lampau');
    }
};
