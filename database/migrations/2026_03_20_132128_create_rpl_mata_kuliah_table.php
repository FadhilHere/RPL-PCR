<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rpl_mata_kuliah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permohonan_rpl_id')->constrained('permohonan_rpl')->cascadeOnDelete();
            $table->foreignId('mata_kuliah_id')->constrained('mata_kuliah');
            $table->enum('jenis_rpl', ['transfer_kredit', 'perolehan_kredit']);
            $table->foreignId('asesor_id')->nullable()->constrained('asesor')->nullOnDelete();
            $table->enum('status', ['menunggu', 'dalam_review', 'diakui', 'tidak_diakui', 'diakui_sebagian'])->default('menunggu');
            $table->string('nilai_akhir', 5)->nullable();
            $table->unsignedTinyInteger('sks_diakui')->nullable();
            $table->text('catatan_asesor')->nullable();
            $table->timestamps();

            $table->unique(['permohonan_rpl_id', 'mata_kuliah_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rpl_mata_kuliah');
    }
};
