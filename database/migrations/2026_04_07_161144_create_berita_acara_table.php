<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('berita_acara', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asesor_id')->constrained('asesor')->cascadeOnDelete();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran')->cascadeOnDelete();
            $table->foreignId('penandatangan_kiri_id')->nullable()->constrained('penandatangan')->nullOnDelete();
            $table->foreignId('penandatangan_kanan_id')->nullable()->constrained('penandatangan')->nullOnDelete();
            $table->date('tanggal_asesmen');
            $table->unsignedSmallInteger('jumlah_peserta')->default(0);
            $table->unsignedSmallInteger('jumlah_hadir')->default(0);
            $table->unsignedSmallInteger('jumlah_tidak_hadir')->default(0);
            $table->boolean('is_locked')->default(false);
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('berita_acara');
    }
};
