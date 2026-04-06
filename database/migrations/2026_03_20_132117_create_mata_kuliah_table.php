<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mata_kuliah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_studi_id')->constrained('program_studi')->cascadeOnDelete();
            $table->string('kode', 20);
            $table->string('nama');
            $table->unsignedTinyInteger('sks');
            $table->unsignedTinyInteger('semester');
            $table->text('deskripsi')->nullable()->comment('Deskripsi MK dari Excel');
            $table->text('cpl')->nullable()->comment('Capaian Pembelajaran Lulusan dari Excel');
            $table->boolean('bisa_rpl')->default(true);
            $table->timestamps();

            $table->unique(['program_studi_id', 'kode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mata_kuliah');
    }
};
