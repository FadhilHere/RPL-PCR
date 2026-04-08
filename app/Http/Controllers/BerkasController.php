<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Models\Asesor;
use App\Models\BeritaAcara;
use App\Models\DokumenBukti;
use App\Models\Penandatangan;
use App\Models\Peserta;
use App\Models\VerifikasiBersama;
use Barryvdh\DomPDF\Facade\Pdf;
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

    // ======================== Berita Acara PDF ========================

    public function downloadBeritaAcara(BeritaAcara $beritaAcara)
    {
        $user = auth()->user();
        $asesor = $user->asesor;

        abort_if(
            ! in_array($user->role, [RoleEnum::Admin, RoleEnum::AdminBaak])
                && ($asesor === null || $beritaAcara->asesor_id !== $asesor->id),
            403
        );

        $beritaAcara->loadMissing([
            'asesor.user', 'tahunAjaran',
            'penandatanganKiri', 'penandatanganKanan',
            'peserta.peserta.user',
        ]);

        $pdf = Pdf::loadView('pdf.berita-acara', ['ba' => $beritaAcara])
            ->setPaper('A4', 'portrait');

        $namaFile = 'Berita_Acara_' . $beritaAcara->tanggal_asesmen->format('Y-m-d') . '.pdf';

        return $pdf->download($namaFile);
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

    // ======================== Tanda Tangan ========================

    public function viewTtdPenandatangan(Penandatangan $penandatangan)
    {
        $user = auth()->user();
        abort_if(! in_array($user->role, [RoleEnum::Admin, RoleEnum::AdminBaak, RoleEnum::AdminPmb, RoleEnum::Asesor]), 403);
        abort_if(! $penandatangan->tanda_tangan || ! Storage::disk('local')->exists($penandatangan->tanda_tangan), 404);

        return response()->file(
            Storage::disk('local')->path($penandatangan->tanda_tangan),
            ['Content-Type' => Storage::disk('local')->mimeType($penandatangan->tanda_tangan)]
        );
    }

    public function viewTtdAsesor(Asesor $asesor)
    {
        $user = auth()->user();
        abort_if(! in_array($user->role, [RoleEnum::Admin, RoleEnum::AdminBaak, RoleEnum::AdminPmb, RoleEnum::Asesor]), 403);
        abort_if(! $asesor->tanda_tangan || ! Storage::disk('local')->exists($asesor->tanda_tangan), 404);

        return response()->file(
            Storage::disk('local')->path($asesor->tanda_tangan),
            ['Content-Type' => Storage::disk('local')->mimeType($asesor->tanda_tangan)]
        );
    }
}
