<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update asesmen_mandiri to use pertanyaan_id instead of cpk_id
        Schema::table('asesmen_mandiri', function (Blueprint $table) {
            // Drop the old foreign key first (this also drops the dependent unique constraint)
            $table->dropForeign(['cpk_id']);

            // Drop the cpk_id column
            $table->dropColumn('cpk_id');

            // Add pertanyaan_id foreign key
            $table->foreignId('pertanyaan_id')
                ->after('rpl_mata_kuliah_id')
                ->constrained('pertanyaan')
                ->cascadeOnDelete();

            // Add new unique constraint
            $table->unique(['rpl_mata_kuliah_id', 'pertanyaan_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert asesmen_mandiri schema
        Schema::table('asesmen_mandiri', function (Blueprint $table) {
            $table->dropForeign(['pertanyaan_id']);
            $table->dropUnique(['rpl_mata_kuliah_id', 'pertanyaan_id']);
            $table->dropColumn('pertanyaan_id');

            $table->foreignId('cpk_id')
                ->after('rpl_mata_kuliah_id')
                ->constrained('cpmk')
                ->restrictOnDelete();

            $table->unique(['rpl_mata_kuliah_id', 'cpk_id']);
        });
    }
};
