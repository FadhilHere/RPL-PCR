<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permohonan_rpl', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peserta_id')->constrained('peserta')->cascadeOnDelete();
            $table->foreignId('program_studi_id')->constrained('program_studi');
            $table->string('nomor_permohonan', 50)->unique();
            $table->enum('status', ['draf', 'diajukan', 'dalam_review', 'disetujui', 'ditolak'])->default('draf');
            $table->text('catatan_admin')->nullable();
            $table->timestamp('tanggal_pengajuan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permohonan_rpl');
    }
};
