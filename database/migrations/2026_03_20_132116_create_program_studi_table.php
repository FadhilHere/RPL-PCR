<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_studi', function (Blueprint $table) {
            $table->id();
            $table->string('kode_sheet', 20)->unique()->comment('Kode sheet Excel: PSTRM, PSTRK, dst');
            $table->string('kode', 20)->unique()->comment('Kode resmi prodi PCR');
            $table->string('nama');
            $table->enum('jenjang', ['D3', 'D4', 'S1', 'S2']);
            $table->unsignedSmallInteger('total_sks');
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_studi');
    }
};
