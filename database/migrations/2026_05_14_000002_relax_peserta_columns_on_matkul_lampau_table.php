<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matkul_lampau', function (Blueprint $table) {
            $table->string('kode_mk')->nullable()->change();
            $table->string('nama_mk')->nullable()->change();
            $table->tinyInteger('sks')->nullable()->change();
        });
    }

    public function down(): void
    {
        throw new \LogicException('Cannot reverse: existing NULL rows would violate NOT NULL constraint.');
    }
};
