<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dokumen_bukti', function (Blueprint $table) {
            $table->foreignId('uploaded_by_user_id')->nullable()->after('keterangan')
                  ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('dokumen_bukti', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by_user_id']);
            $table->dropColumn('uploaded_by_user_id');
        });
    }
};
