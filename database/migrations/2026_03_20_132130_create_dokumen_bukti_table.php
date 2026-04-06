<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumen_bukti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permohonan_rpl_id')->constrained('permohonan_rpl')->cascadeOnDelete();
            $table->foreignId('asesmen_mandiri_id')->nullable()->constrained('asesmen_mandiri')->nullOnDelete();
            $table->enum('jenis_dokumen', [
                'ijazah', 'transkrip', 'rps_silabus', 'sertifikat',
                'logbook', 'surat_keterangan', 'cv', 'penilaian_kinerja',
                'karya_monumental', 'lainnya',
            ]);
            $table->string('nama_dokumen');
            $table->string('berkas', 500);
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen_bukti');
    }
};
