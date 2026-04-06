<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rpl_mata_kuliah', function (Blueprint $table) {
            $table->dropForeign(['mata_kuliah_id']);
            $table->foreign('mata_kuliah_id')
                  ->references('id')->on('mata_kuliah')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rpl_mata_kuliah', function (Blueprint $table) {
            $table->dropForeign(['mata_kuliah_id']);
            $table->foreign('mata_kuliah_id')
                  ->references('id')->on('mata_kuliah');
        });
    }
};
