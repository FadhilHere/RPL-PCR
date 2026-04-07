<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asesor_permohonan', function (Blueprint $table) {
            $table->foreignId('asesor_id')->constrained('asesor')->cascadeOnDelete();
            $table->foreignId('permohonan_rpl_id')->constrained('permohonan_rpl')->cascadeOnDelete();
            $table->primary(['asesor_id', 'permohonan_rpl_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asesor_permohonan');
    }
};
