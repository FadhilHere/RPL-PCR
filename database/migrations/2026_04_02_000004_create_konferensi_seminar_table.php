<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('konferensi_seminar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peserta_id')->constrained('peserta')->cascadeOnDelete();
            $table->string('tahun', 4);
            $table->string('judul_kegiatan');
            $table->string('penyelenggara');
            $table->string('peran', 100)->nullable()->comment('Panitia / Pemohon / Pembicara');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('konferensi_seminar');
    }
};
