<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Models\DokumenBukti;
use App\Models\Peserta;
use App\Models\VerifikasiBersama;
use Illuminate\Support\Facades\Storage;

class BerkasController extends Controller
{
    // ======================== Dokumen Bukti ========================

    private function authorizeDokumen(DokumenBukti $dokumen): void
    {
        $user    = auth()->user();
        $peserta = $user->peserta;

        $canAccess = in_array($user->role, [RoleEnum::Admin, RoleEnum::Asesor])
            || ($peserta && $dokumen->peserta_id === $peserta->id);

        abort_if(! $canAccess, 403);
        abort_if(! Storage::disk('local')->exists($dokumen->berkas), 404);
    }

    public function viewDokumen(DokumenBukti $dokumen)
    {
        $this->authorizeDokumen($dokumen);

        $path     = Storage::disk('local')->path($dokumen->berkas);
        $mimeType = Storage::disk('local')->mimeType($dokumen->berkas);

        return response()->file($path, [
            'Content-Type'        => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $dokumen->nama_dokumen . '"',
        ]);
    }

    public function downloadDokumen(DokumenBukti $dokumen)
    {
        $this->authorizeDokumen($dokumen);

        $dokumen->loadMissing('peserta.user');

        $ext          = strtolower(pathinfo($dokumen->berkas, PATHINFO_EXTENSION));
        $namaFile     = preg_replace('/[\/\\\\\:]/', '_', $dokumen->nama_dokumen);
        $namaPeserta  = $dokumen->peserta?->user?->nama ?? 'peserta';
        $namaPeserta  = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $namaPeserta);
        $downloadName = $namaFile . '_' . $namaPeserta . ($ext ? '.' . $ext : '');

        return Storage::disk('local')->download($dokumen->berkas, $downloadName);
    }

    // ======================== Verifikasi Bersama ========================

    private function authorizeVerifikasi(VerifikasiBersama $vb): void
    {
        $user = auth()->user();

        $canAccess = in_array($user->role, [RoleEnum::Admin, RoleEnum::Asesor])
            || ($user->peserta && $vb->permohonan_rpl_id === $user->peserta->permohonanRpl?->id);

        abort_if(! $canAccess, 403);
        abort_if(! $vb->berkas || ! Storage::disk('local')->exists($vb->berkas), 404);
    }

    public function viewVerifikasi(VerifikasiBersama $vb)
    {
        $this->authorizeVerifikasi($vb);

        $path     = Storage::disk('local')->path($vb->berkas);
        $mimeType = Storage::disk('local')->mimeType($vb->berkas);

        return response()->file($path, [
            'Content-Type'        => $mimeType,
            'Content-Disposition' => 'inline; filename="Berkas_Verifikasi_' . $vb->id . '"',
        ]);
    }

    public function downloadVerifikasi(VerifikasiBersama $vb)
    {
        $this->authorizeVerifikasi($vb);

        $vb->loadMissing('permohonanRpl.peserta.user');

        $ext         = strtolower(pathinfo($vb->berkas, PATHINFO_EXTENSION));
        $namaPeserta = $vb->permohonanRpl?->peserta?->user?->nama ?? 'peserta';
        $namaPeserta = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $namaPeserta);
        $downloadName = 'Berkas_Verifikasi_' . $namaPeserta . ($ext ? '.' . $ext : '');

        return Storage::disk('local')->download($vb->berkas, $downloadName);
    }

    // ======================== Foto Peserta ========================

    public function viewFoto(Peserta $peserta)
    {
        $user = auth()->user();
        abort_if(
            ! in_array($user->role, [RoleEnum::Admin, RoleEnum::Asesor])
                && $user->peserta?->id !== $peserta->id,
            403
        );

        abort_if(! $peserta->foto, 404);

        // Cek public disk dulu (upload baru), lalu fallback ke local
        if (Storage::disk('public')->exists($peserta->foto)) {
            $path     = Storage::disk('public')->path($peserta->foto);
            $mimeType = Storage::disk('public')->mimeType($peserta->foto);
        } elseif (Storage::disk('local')->exists($peserta->foto)) {
            $path     = Storage::disk('local')->path($peserta->foto);
            $mimeType = Storage::disk('local')->mimeType($peserta->foto);
        } else {
            abort(404);
        }

        return response()->file($path, ['Content-Type' => $mimeType]);
    }
}
