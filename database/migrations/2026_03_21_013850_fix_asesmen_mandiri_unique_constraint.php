<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asesmen_mandiri', function (Blueprint $table) {
            // Drop the stale unique index left over from the cpk_id column rename.
            // When cpk_id was dropped, MySQL may have kept the composite index
            // as a single-column index on rpl_mata_kuliah_id alone.
            $table->dropUnique('asesmen_mandiri_rpl_mata_kuliah_id_cpk_id_unique');
        });

        // Add the correct composite unique constraint if it doesn't already exist
        $indexExists = collect(DB::select("SHOW INDEX FROM `asesmen_mandiri` WHERE Key_name = 'asesmen_mandiri_rpl_mata_kuliah_id_pertanyaan_id_unique'"))->isNotEmpty();

        if (! $indexExists) {
            Schema::table('asesmen_mandiri', function (Blueprint $table) {
                $table->unique(['rpl_mata_kuliah_id', 'pertanyaan_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('asesmen_mandiri', function (Blueprint $table) {
            $table->dropUnique(['rpl_mata_kuliah_id', 'pertanyaan_id']);
            $table->unique(['rpl_mata_kuliah_id', 'pertanyaan_id'], 'asesmen_mandiri_rpl_mata_kuliah_id_cpk_id_unique');
        });
    }
};
