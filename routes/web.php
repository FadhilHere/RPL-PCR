<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use App\Http\Controllers\BerkasController;

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// ===================== PESERTA =====================
Route::prefix('peserta')->middleware(['auth', 'verified', 'role:peserta'])->group(function () {
    Volt::route('dashboard', 'peserta.dashboard')->name('peserta.dashboard');
    Volt::route('pengajuan', 'peserta.pengajuan.index')->name('peserta.pengajuan.index');
    Volt::route('pengajuan/buat', 'peserta.pengajuan.buat')->name('peserta.pengajuan.buat');
    Volt::route('pengajuan/{permohonan}/asesmen', 'peserta.pengajuan.asesmen')->name('peserta.pengajuan.asesmen');
    Volt::route('pengajuan/{permohonan}/dokumen', 'peserta.pengajuan.dokumen')->name('peserta.pengajuan.dokumen');
    Volt::route('berkas', 'peserta.berkas.index')->name('peserta.berkas.index');
    Volt::route('profil', 'peserta.profil.index')->name('peserta.profil.index');
});

// ===================== ASESOR =====================
Route::prefix('asesor')->middleware(['auth', 'verified', 'role:asesor'])->group(function () {
    Volt::route('dashboard', 'asesor.dashboard')->name('asesor.dashboard');
    Volt::route('materi', 'asesor.materi.index')->name('asesor.materi.index');
    Volt::route('materi/{prodi}', 'asesor.materi.prodi')->name('asesor.materi.prodi');
    Volt::route('pengajuan', 'asesor.pengajuan.index')->name('asesor.pengajuan.index');
    Volt::route('pengajuan/{permohonan}/evaluasi', 'asesor.evaluasi.index')->name('asesor.evaluasi.index');
    Volt::route('pengajuan/{permohonan}/evaluasi/resume', 'asesor.evaluasi.resume')->name('asesor.evaluasi.resume');
    Volt::route('pleno', 'asesor.pleno.index')->name('asesor.pleno.index');
});

// ===================== ADMIN =====================
Route::prefix('admin')->middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Volt::route('dashboard', 'admin.dashboard')->name('admin.dashboard');
    Volt::route('materi', 'admin.materi.index')->name('admin.materi.index');
    Volt::route('materi/{prodi}', 'admin.materi.prodi')->name('admin.materi.prodi');
    Volt::route('akun', 'admin.akun.index')->name('admin.akun.index');
    Volt::route('akun/{peserta}/berkas', 'admin.akun.berkas')->name('admin.akun.berkas');
    Volt::route('akun/{peserta}/profil', 'admin.akun.profil')->name('admin.akun.profil');
    Volt::route('prodi', 'admin.prodi.index')->name('admin.prodi.index');
    Volt::route('pengajuan', 'admin.pengajuan.index')->name('admin.pengajuan.index');
    Volt::route('pengajuan/{permohonan}', 'admin.pengajuan.detail')->name('admin.pengajuan.detail');
    Volt::route('tahun-ajaran', 'admin.tahun-ajaran.index')->name('admin.tahun-ajaran.index');
    Volt::route('jadwal', 'admin.jadwal.index')->name('admin.jadwal.index');
    Volt::route('pleno', 'admin.pleno.index')->name('admin.pleno.index');
    Volt::route('pleno/{permohonan}', 'admin.pleno.detail')->name('admin.pleno.detail');
});

// ===================== BERKAS SERVE =====================
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('berkas/{dokumen}/view', [BerkasController::class, 'viewDokumen'])->name('berkas.view');
    Route::get('berkas/{dokumen}/download', [BerkasController::class, 'downloadDokumen'])->name('berkas.download');
    Route::get('verifikasi-bersama/{vb}/view', [BerkasController::class, 'viewVerifikasi'])->name('verifikasi-bersama.view');
    Route::get('verifikasi-bersama/{vb}/download', [BerkasController::class, 'downloadVerifikasi'])->name('verifikasi-bersama.download');
    Route::get('peserta/{peserta}/foto', [BerkasController::class, 'viewFoto'])->name('peserta.foto');
});

// ===================== LOGOUT =====================
Route::post('logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->middleware('auth')->name('logout');

require __DIR__ . '/auth.php';
