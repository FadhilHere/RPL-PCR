<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permohonan_rpl', function (Blueprint $table) {
            $table->boolean('pembayaran_terverifikasi')->default(false)->after('catatan_admin');
            $table->timestamp('tanggal_verifikasi_pembayaran')->nullable()->after('pembayaran_terverifikasi');
            $table->foreignId('admin_verifikator_id')->nullable()->after('tanggal_verifikasi_pembayaran')
                  ->constrained('users')->nullOnDelete();
            $table->foreignId('tahun_ajaran_id')->nullable()->after('admin_verifikator_id')
                  ->constrained('tahun_ajaran')->nullOnDelete();
            $table->enum('semester', ['ganjil', 'genap'])->nullable()->after('tahun_ajaran_id');
        });
    }

    public function down(): void
    {
        Schema::table('permohonan_rpl', function (Blueprint $table) {
            $table->dropForeign(['admin_verifikator_id']);
            $table->dropForeign(['tahun_ajaran_id']);
            $table->dropColumn([
                'pembayaran_terverifikasi',
                'tanggal_verifikasi_pembayaran',
                'admin_verifikator_id',
                'tahun_ajaran_id',
                'semester',
            ]);
        });
    }
};
