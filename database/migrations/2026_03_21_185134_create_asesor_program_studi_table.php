<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asesor_program_studi', function (Blueprint $table) {
            $table->foreignId('asesor_id')->constrained('asesor')->cascadeOnDelete();
            $table->foreignId('program_studi_id')->constrained('program_studi')->cascadeOnDelete();
            $table->primary(['asesor_id', 'program_studi_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asesor_program_studi');
    }
};
