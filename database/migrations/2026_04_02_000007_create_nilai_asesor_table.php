<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nilai_asesor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asesmen_mandiri_id')->constrained('asesmen_mandiri')->cascadeOnDelete();
            $table->foreignId('asesor_id')->constrained('asesor');
            $table->unsignedTinyInteger('nilai')->comment('Skala 1-5');
            $table->text('catatan')->nullable();
            $table->timestamp('dinilai_pada')->nullable();
            $table->timestamps();

            $table->unique(['asesmen_mandiri_id', 'asesor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nilai_asesor');
    }
};
