<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluasi_vatm', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asesmen_mandiri_id')->unique()->constrained('asesmen_mandiri')->cascadeOnDelete();
            $table->foreignId('asesor_id')->constrained('asesor');
            $table->boolean('valid')->nullable()->comment('V: Bukti relevan dan sesuai standar');
            $table->boolean('autentik')->nullable()->comment('A: Bukti benar milik pemohon');
            $table->boolean('terkini')->nullable()->comment('T: Bukti masih up-to-date');
            $table->boolean('memadai')->nullable()->comment('M: Bukti cukup untuk dinilai');
            $table->text('catatan')->nullable();
            $table->timestamp('dievaluasi_pada')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluasi_vatm');
    }
};
