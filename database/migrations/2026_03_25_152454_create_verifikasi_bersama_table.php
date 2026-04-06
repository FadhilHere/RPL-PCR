<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('verifikasi_bersama', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permohonan_rpl_id')->constrained('permohonan_rpl')->cascadeOnDelete();
            $table->foreignId('asesor_id')->nullable()->constrained('asesor')->nullOnDelete();
            $table->timestamp('jadwal');
            $table->enum('status', ['terjadwal', 'selesai', 'minta_revisi'])->default('terjadwal');
            $table->text('catatan')->nullable();
            $table->string('berkas', 500)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verifikasi_bersama');
    }
};
