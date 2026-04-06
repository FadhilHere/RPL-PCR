<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sk_rekognisi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permohonan_rpl_id')->unique()->constrained('permohonan_rpl')->cascadeOnDelete();
            $table->string('nomor_sk', 100)->unique();
            $table->date('tanggal_sk');
            $table->string('berkas', 500)->nullable();
            $table->foreignId('diterbitkan_oleh')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sk_rekognisi');
    }
};
