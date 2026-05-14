<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permohonan_rpl', function (Blueprint $table) {
            $table->timestamp('dirilis_pada')->nullable()->after('status');
            $table->foreignId('dirilis_oleh_user_id')->nullable()->after('dirilis_pada')
                  ->constrained('users')->nullOnDelete();
        });

        // Backfill: permohonan yang sudah Disetujui/Ditolak sebelum fitur rilis
        // dianggap sudah dirilis agar tidak tiba-tiba disembunyikan dari peserta.
        DB::table('permohonan_rpl')
            ->whereIn('status', ['disetujui', 'ditolak'])
            ->whereNull('dirilis_pada')
            ->update(['dirilis_pada' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('permohonan_rpl', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dirilis_oleh_user_id');
            $table->dropColumn('dirilis_pada');
        });
    }
};
