<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penghargaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peserta_id')->constrained('peserta')->cascadeOnDelete();
            $table->string('tahun', 4);
            $table->string('bentuk_penghargaan');
            $table->string('pemberi');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penghargaan');
    }
};
