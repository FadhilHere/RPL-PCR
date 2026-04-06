<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('konsultasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peserta_id')->constrained('peserta')->cascadeOnDelete();
            $table->foreignId('asesor_id')->nullable()->constrained('asesor')->nullOnDelete();
            $table->foreignId('permohonan_rpl_id')->nullable()->constrained('permohonan_rpl')->nullOnDelete();
            $table->enum('jenis', ['awal', 'lanjutan'])->default('awal');
            $table->timestamp('jadwal');
            $table->enum('status', ['terjadwal', 'selesai', 'dibatalkan'])->default('terjadwal');
            $table->text('catatan_konsultasi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('konsultasi');
    }
};
