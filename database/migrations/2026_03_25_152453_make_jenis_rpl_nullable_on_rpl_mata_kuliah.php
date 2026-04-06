<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // jenis_rpl dibuat nullable — akan ditentukan pada sprint berikutnya
        DB::statement("ALTER TABLE rpl_mata_kuliah MODIFY COLUMN jenis_rpl ENUM('transfer_kredit','perolehan_kredit') NULL DEFAULT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE rpl_mata_kuliah MODIFY COLUMN jenis_rpl ENUM('transfer_kredit','perolehan_kredit') NOT NULL");
    }
};
