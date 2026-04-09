<?php

namespace App\Http\Controllers;

use App\Enums\PosisiPenandatanganEnum;
use App\Enums\RoleEnum;
use App\Enums\StatusRplMataKuliahEnum;
use App\Enums\StatusVerifikasiEnum;
use App\Enums\JenisRplEnum;
use App\Models\Asesor;
use App\Models\BeritaAcara;
use App\Models\DokumenBukti;
use App\Models\Penandatangan;
use App\Models\Peserta;
use App\Models\PermohonanRpl;
use App\Models\ProgramStudi;
use App\Models\TahunAjaran;
use App\Models\VerifikasiBersama;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

class BerkasController extends Controller
{
    private function resolveTargetAsesorForBa(?int $asesorId): Asesor
    {
        $user = auth()->user();

        if ($user->role === RoleEnum::Asesor) {
            $asesor = $user->asesor;
            abort_if(!$asesor, 403);

            return $asesor;
        }

        abort_if(!in_array($user->role, [RoleEnum::Admin, RoleEnum::AdminBaak]), 403);
        abort_if(!$asesorId, 422, 'Asesor wajib dipilih.');

        return Asesor::findOrFail($asesorId);
    }

    private function buildBeritaAcaraRows(
        Asesor $asesor,
        int $tahunAjaranId,
        ?string $tanggalDari,
        ?string $tanggalSampai,
    ) {
        $permohonanList = PermohonanRpl::with([
            'peserta.user',
            'rplMataKuliah.mataKuliah',
            'verifikasiBersama' => fn($q) => $q
                ->whereIn('status', [StatusVerifikasiEnum::Terjadwal->value, StatusVerifikasiEnum::Selesai->value])
                ->when($tanggalDari, fn($qq) => $qq->whereDate('jadwal', '>=', $tanggalDari))
                ->when($tanggalSampai, fn($qq) => $qq->whereDate('jadwal', '<=', $tanggalSampai))
                ->orderByDesc('jadwal')
                ->orderByDesc('id'),
        ])
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->whereHas('asesor', fn($q) => $q->where('asesor.id', $asesor->id))
            ->whereHas('verifikasiBersama', function ($q) use ($tanggalDari, $tanggalSampai) {
                $q->whereIn('status', [StatusVerifikasiEnum::Terjadwal->value, StatusVerifikasiEnum::Selesai->value])
                    ->when($tanggalDari, fn($qq) => $qq->whereDate('jadwal', '>=', $tanggalDari))
                    ->when($tanggalSampai, fn($qq) => $qq->whereDate('jadwal', '<=', $tanggalSampai));
            })
            ->get();

        return $permohonanList
            ->map(function (PermohonanRpl $permohonan) {
                $verifikasi = $permohonan->verifikasiBersama->first();

                if (!$verifikasi || !$verifikasi->jadwal) {
                    return null;
                }

                $jenisRpl = $permohonan->jenis_rpl;
                if (!$jenisRpl instanceof JenisRplEnum) {
                    $jenisRpl = is_string($jenisRpl) ? JenisRplEnum::tryFrom($jenisRpl) : null;
                }

                $statusVerifikasi = $verifikasi->status instanceof StatusVerifikasiEnum
                    ? $verifikasi->status
                    : StatusVerifikasiEnum::tryFrom((string) $verifikasi->status);

                $totalSks = $permohonan->rplMataKuliah
                    ->filter(function ($m) {
                        $statusMataKuliah = $m->status instanceof StatusRplMataKuliahEnum
                            ? $m->status
                            : StatusRplMataKuliahEnum::tryFrom((string) $m->status);

                        return $statusMataKuliah === StatusRplMataKuliahEnum::Diakui;
                    })
                    ->sum(fn($m) => $m->mataKuliah->sks ?? 0);

                $keteranganHadir = match ($statusVerifikasi) {
                    StatusVerifikasiEnum::Selesai => 'Hadir',
                    StatusVerifikasiEnum::Terjadwal => 'Belum Asesmen',
                    default => '-',
                };

                return [
                    'nama_peserta' => $permohonan->peserta?->user?->nama ?? '—',
                    'jenis_rpl' => $jenisRpl?->label() ?? '-',
                    'total_sks_diperoleh' => $totalSks,
                    'tanggal_asesi' => Carbon::parse($verifikasi->jadwal),
                    'keterangan_hadir' => $keteranganHadir,
                ];
            })
            ->filter()
            ->sortBy(fn($row) => $row['tanggal_asesi']->timestamp)
            ->values();
    }

    private function resolveBeritaAcaraDinamisPayload(array $validated): array
    {
        $asesor = $this->resolveTargetAsesorForBa($validated['asesor_id'] ?? null)->loadMissing('user');

        $tahunAjaranId = $validated['tahun_ajaran_id']
            ?? TahunAjaran::aktif()->value('id')
            ?? TahunAjaran::query()->orderByDesc('id')->value('id');

        abort_if(!$tahunAjaranId, 422, 'Tahun ajaran belum tersedia.');

        $tahunAjaran = TahunAjaran::findOrFail($tahunAjaranId);
        $tanggalDari = isset($validated['tanggal_dari']) ? Carbon::parse($validated['tanggal_dari']) : null;
        $tanggalSampai = isset($validated['tanggal_sampai']) ? Carbon::parse($validated['tanggal_sampai']) : null;

        $rows = $this->buildBeritaAcaraRows(
            asesor: $asesor,
            tahunAjaranId: (int) $tahunAjaranId,
            tanggalDari: $tanggalDari?->toDateString(),
            tanggalSampai: $tanggalSampai?->toDateString(),
        );

        $penandatanganKiri = Penandatangan::query()
            ->where('posisi', PosisiPenandatanganEnum::Kiri)
            ->where('aktif', true)
            ->orderBy('urutan')
            ->first();

        $periodeTanggal = 'Semua tanggal asesi';
        if ($tanggalDari && $tanggalSampai) {
            $periodeTanggal = $tanggalDari->locale('id')->isoFormat('D MMMM YYYY') . ' s.d. ' . $tanggalSampai->locale('id')->isoFormat('D MMMM YYYY');
        } elseif ($tanggalDari) {
            $periodeTanggal = 'Mulai ' . $tanggalDari->locale('id')->isoFormat('D MMMM YYYY');
        } elseif ($tanggalSampai) {
            $periodeTanggal = 'Sampai ' . $tanggalSampai->locale('id')->isoFormat('D MMMM YYYY');
        }

        return compact('asesor', 'tahunAjaran', 'rows', 'tanggalDari', 'tanggalSampai', 'penandatanganKiri', 'periodeTanggal');
    }

    private function buildBeritaAcaraDinamisFilename(Asesor $asesor, TahunAjaran $tahunAjaran, string $extension): string
    {
        $asesorSlug = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $asesor->user?->nama ?? 'asesor');
        $tahunSlug = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $tahunAjaran->nama ?? 'tahun_ajaran');

        return 'Berita_Acara_' . $asesorSlug . '_' . $tahunSlug . '_' . now()->format('Ymd_His') . '.' . $extension;
    }

    // ======================== Dokumen Bukti ========================

    private function authorizeDokumen(DokumenBukti $dokumen): void
    {
        $user = auth()->user();
        $peserta = $user->peserta;

        $canAccess = in_array($user->role, [RoleEnum::Admin, RoleEnum::Asesor])
            || ($peserta && $dokumen->peserta_id === $peserta->id);

        abort_if(!$canAccess, 403);
        abort_if(!Storage::disk('local')->exists($dokumen->berkas), 404);
    }

    public function viewDokumen(DokumenBukti $dokumen)
    {
        $this->authorizeDokumen($dokumen);

        $path = Storage::disk('local')->path($dokumen->berkas);
        $mimeType = Storage::disk('local')->mimeType($dokumen->berkas);

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $dokumen->nama_dokumen . '"',
        ]);
    }

    public function downloadDokumen(DokumenBukti $dokumen)
    {
        $this->authorizeDokumen($dokumen);

        $dokumen->loadMissing('peserta.user');

        $ext = strtolower(pathinfo($dokumen->berkas, PATHINFO_EXTENSION));
        $namaFile = preg_replace('/[\/\\\\\:]/', '_', $dokumen->nama_dokumen);
        $namaPeserta = $dokumen->peserta?->user?->nama ?? 'peserta';
        $namaPeserta = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $namaPeserta);
        $downloadName = $namaFile . '_' . $namaPeserta . ($ext ? '.' . $ext : '');

        return Storage::disk('local')->download($dokumen->berkas, $downloadName);
    }

    // ======================== Verifikasi Bersama ========================

    private function authorizeVerifikasi(VerifikasiBersama $vb): void
    {
        $user = auth()->user();

        $canAccess = in_array($user->role, [RoleEnum::Admin, RoleEnum::Asesor])
            || ($user->peserta && $vb->permohonan_rpl_id === $user->peserta->permohonanRpl?->id);

        abort_if(!$canAccess, 403);
        abort_if(!$vb->berkas || !Storage::disk('local')->exists($vb->berkas), 404);
    }

    public function viewVerifikasi(VerifikasiBersama $vb)
    {
        $this->authorizeVerifikasi($vb);

        $path = Storage::disk('local')->path($vb->berkas);
        $mimeType = Storage::disk('local')->mimeType($vb->berkas);

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="Berkas_Verifikasi_' . $vb->id . '"',
        ]);
    }

    public function downloadVerifikasi(VerifikasiBersama $vb)
    {
        $this->authorizeVerifikasi($vb);

        $vb->loadMissing('permohonanRpl.peserta.user');

        $ext = strtolower(pathinfo($vb->berkas, PATHINFO_EXTENSION));
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
            !in_array($user->role, [RoleEnum::Admin, RoleEnum::AdminBaak])
            && ($asesor === null || $beritaAcara->asesor_id !== $asesor->id),
            403
        );

        $beritaAcara->loadMissing([
            'asesor.user',
            'tahunAjaran',
            'penandatanganKiri',
            'penandatanganKanan',
            'peserta.peserta.user',
        ]);

        $pdf = Pdf::loadView('pdf.berita-acara', ['ba' => $beritaAcara])
            ->setPaper('A4', 'portrait');

        $namaFile = 'Berita_Acara_' . $beritaAcara->tanggal_asesmen->format('Y-m-d') . '.pdf';

        return $pdf->download($namaFile);
    }

    public function downloadBeritaAcaraDinamis()
    {
        $validated = Validator::make(request()->all(), [
            'asesor_id' => ['nullable', 'integer', 'exists:asesor,id'],
            'tahun_ajaran_id' => ['nullable', 'integer', 'exists:tahun_ajaran,id'],
            'tanggal_dari' => ['nullable', 'date'],
            'tanggal_sampai' => ['nullable', 'date', 'after_or_equal:tanggal_dari'],
        ])->validate();

        $payload = $this->resolveBeritaAcaraDinamisPayload($validated);

        $pdf = Pdf::loadView('pdf.berita-acara-dinamis', $payload)->setPaper('A4', 'portrait');

        $namaFile = $this->buildBeritaAcaraDinamisFilename($payload['asesor'], $payload['tahunAjaran'], 'pdf');

        return $pdf->download($namaFile);
    }

    public function downloadBeritaAcaraDinamisWord()
    {
        $validated = Validator::make(request()->all(), [
            'asesor_id' => ['nullable', 'integer', 'exists:asesor,id'],
            'tahun_ajaran_id' => ['nullable', 'integer', 'exists:tahun_ajaran,id'],
            'tanggal_dari' => ['nullable', 'date'],
            'tanggal_sampai' => ['nullable', 'date', 'after_or_equal:tanggal_dari'],
        ])->validate();

        $payload = $this->resolveBeritaAcaraDinamisPayload($validated);

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(11);

        $section = $phpWord->addSection([
            'marginTop' => 1450,
            'marginBottom' => 1150,
            'marginLeft' => 1000,
            'marginRight' => 1000,
            'headerHeight' => 700,
            'footerHeight' => 700,
        ]);

        $center = ['alignment' => Jc::CENTER];
        $left = ['alignment' => Jc::START];
        $logoPath = public_path('img/logo_pcr.png');

        // Header: logo dokumen ditempatkan di area header asli Word.
        $header = $section->addHeader();
        if (file_exists($logoPath)) {
            $header->addImage($logoPath, [
                'width' => 120,
                'alignment' => Jc::CENTER,
            ]);
        }

        // Footer: alamat dan kontak ditempatkan di footer agar selalu rata bawah halaman.
        $footer = $section->addFooter();
        $footer->addText(str_repeat('_', 90), ['size' => 8], $center);
        $footer->addText('Jl. Umban Sari No.1, Umban Sari, Kec. Rumbai, Kota Pekanbaru, Riau 28265', ['size' => 9], $center);
        $footer->addText('(0761) 53939 | pcr.ac.id', ['size' => 9], $center);

        $section->addTextBreak(1);
        $section->addText('BERITA ACARA ASESMEN RPL', ['bold' => true, 'size' => 14], $center);
        $section->addTextBreak(1);

        $phpWord->addTableStyle('BaMetaTable', [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 45,
        ]);
        $metaTable = $section->addTable('BaMetaTable');
        $metaRows = [
            ['Nama Asesor', $payload['asesor']->user?->nama ?? '—'],
            ['Tahun Ajaran', $payload['tahunAjaran']->nama ?? '—'],
            ['Periode Tanggal Asesi', $payload['periodeTanggal']],
            ['Tanggal Cetak', now()->locale('id')->isoFormat('D MMMM YYYY, HH:mm') . ' WIB'],
        ];

        foreach ($metaRows as [$label, $value]) {
            $metaTable->addRow();
            $metaTable->addCell(2500)->addText($label, ['size' => 10]);
            $metaTable->addCell(320)->addText(':', ['size' => 10], $center);
            $metaTable->addCell(9000)->addText((string) $value, ['size' => 11]);
        }

        $section->addTextBreak(1);
        $section->addText('Total peserta terjadwal: ' . $payload['rows']->count(), ['size' => 11]);
        $section->addTextBreak(1);

        $phpWord->addTableStyle('BaPesertaTable', [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80,
        ]);
        $table = $section->addTable('BaPesertaTable');

        $headerStyle = ['bold' => true, 'size' => 10];
        $table->addRow();
        $table->addCell(700)->addText('No', $headerStyle, $center);
        $table->addCell(3200)->addText('Nama Peserta', $headerStyle, $center);
        $table->addCell(1600)->addText('Jenis RPL', $headerStyle, $center);
        $table->addCell(1600)->addText('Total SKS Diperoleh', $headerStyle, $center);
        $table->addCell(2000)->addText('Tanggal Asesi', $headerStyle, $center);
        $table->addCell(1900)->addText('Keterangan Hadir', $headerStyle, $center);

        if ($payload['rows']->isEmpty()) {
            $table->addRow();
            $table->addCell(11000, ['gridSpan' => 6])->addText('Tidak ada data peserta untuk filter yang dipilih.', ['size' => 10], $center);
        } else {
            foreach ($payload['rows'] as $idx => $row) {
                $tanggalAsesi = $row['tanggal_asesi'] instanceof Carbon
                    ? $row['tanggal_asesi']->locale('id')->isoFormat('D MMMM YYYY')
                    : (string) $row['tanggal_asesi'];

                $table->addRow();
                $table->addCell(700)->addText((string) ($idx + 1), ['size' => 10], $center);
                $table->addCell(3200)->addText($row['nama_peserta'], ['size' => 10]);
                $table->addCell(1600)->addText($row['jenis_rpl'], ['size' => 10], $center);
                $table->addCell(1600)->addText((string) $row['total_sks_diperoleh'], ['size' => 10], $center);
                $table->addCell(2000)->addText($tanggalAsesi, ['size' => 10], $center);
                $table->addCell(1900)->addText($row['keterangan_hadir'], ['size' => 10], $center);
            }
        }

        // Area tanda tangan dibuat tanpa border agar tidak terlihat seperti tabel kasar.
        $section->addTextBreak(2);
        $phpWord->addTableStyle('BaTtdTable', [
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin' => 10,
        ]);
        $ttdTable = $section->addTable('BaTtdTable');
        $ttdTable->addRow();
        $cellKiri = $ttdTable->addCell(5500, ['borderSize' => 0, 'borderColor' => 'FFFFFF']);
        $cellKanan = $ttdTable->addCell(5500, ['borderSize' => 0, 'borderColor' => 'FFFFFF']);

        $cellKiri->addText($payload['penandatanganKiri']?->jabatan ?? 'Mengetahui,', ['size' => 11], $left);
        $cellKiri->addTextBreak(1);
        if ($payload['penandatanganKiri']?->tanda_tangan && Storage::disk('local')->exists($payload['penandatanganKiri']->tanda_tangan)) {
            $cellKiri->addImage(Storage::disk('local')->path($payload['penandatanganKiri']->tanda_tangan), [
                'width' => 130,
                'height' => 60,
                'alignment' => Jc::START,
            ]);
        } else {
            $cellKiri->addTextBreak(3);
        }
        $cellKiri->addText($payload['penandatanganKiri']?->nama ?? '____________________', ['bold' => true, 'size' => 11], $left);
        if ($payload['penandatanganKiri']?->nip) {
            $cellKiri->addText('NIP. ' . $payload['penandatanganKiri']->nip, ['size' => 10], $left);
        }

        $cellKanan->addText('Asesor,', ['size' => 11], $left);
        $cellKanan->addTextBreak(1);
        if ($payload['asesor']?->tanda_tangan && Storage::disk('local')->exists($payload['asesor']->tanda_tangan)) {
            $cellKanan->addImage(Storage::disk('local')->path($payload['asesor']->tanda_tangan), [
                'width' => 130,
                'height' => 60,
                'alignment' => Jc::START,
            ]);
        } else {
            $cellKanan->addTextBreak(3);
        }
        $cellKanan->addText($payload['asesor']?->user?->nama ?? '____________________', ['bold' => true, 'size' => 11], $left);
        if ($payload['asesor']?->nidn) {
            $cellKanan->addText('NIDN. ' . $payload['asesor']->nidn, ['size' => 10], $left);
        }

        $filename = $this->buildBeritaAcaraDinamisFilename($payload['asesor'], $payload['tahunAjaran'], 'docx');
        $tmpPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;
        IOFactory::createWriter($phpWord, 'Word2007')->save($tmpPath);

        return response()->download(
            $tmpPath,
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
        )->deleteFileAfterSend(true);
    }

    // ======================== Foto Peserta ========================

    public function viewFoto(Peserta $peserta)
    {
        $user = auth()->user();
        abort_if(
            !in_array($user->role, [RoleEnum::Admin, RoleEnum::Asesor])
            && $user->peserta?->id !== $peserta->id,
            403
        );

        abort_if(!$peserta->foto, 404);

        // Cek public disk dulu (upload baru), lalu fallback ke local
        if (Storage::disk('public')->exists($peserta->foto)) {
            $path = Storage::disk('public')->path($peserta->foto);
            $mimeType = Storage::disk('public')->mimeType($peserta->foto);
        } elseif (Storage::disk('local')->exists($peserta->foto)) {
            $path = Storage::disk('local')->path($peserta->foto);
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
        abort_if(!in_array($user->role, [RoleEnum::Admin, RoleEnum::AdminBaak, RoleEnum::AdminPmb, RoleEnum::Asesor]), 403);
        abort_if(!$penandatangan->tanda_tangan || !Storage::disk('local')->exists($penandatangan->tanda_tangan), 404);

        return response()->file(
            Storage::disk('local')->path($penandatangan->tanda_tangan),
            ['Content-Type' => Storage::disk('local')->mimeType($penandatangan->tanda_tangan)]
        );
    }

    public function viewTtdAsesor(Asesor $asesor)
    {
        $user = auth()->user();
        abort_if(!in_array($user->role, [RoleEnum::Admin, RoleEnum::AdminBaak, RoleEnum::AdminPmb, RoleEnum::Asesor]), 403);
        abort_if(!$asesor->tanda_tangan || !Storage::disk('local')->exists($asesor->tanda_tangan), 404);

        return response()->file(
            Storage::disk('local')->path($asesor->tanda_tangan),
            ['Content-Type' => Storage::disk('local')->mimeType($asesor->tanda_tangan)]
        );
    }

    public function viewTtdProgramStudi(ProgramStudi $programStudi)
    {
        $user = auth()->user();
        abort_if(!in_array($user->role, [RoleEnum::Admin, RoleEnum::AdminBaak, RoleEnum::AdminPmb, RoleEnum::Asesor]), 403);
        abort_if(!$programStudi->ketua_tanda_tangan || !Storage::disk('local')->exists($programStudi->ketua_tanda_tangan), 404);

        return response()->file(
            Storage::disk('local')->path($programStudi->ketua_tanda_tangan),
            ['Content-Type' => Storage::disk('local')->mimeType($programStudi->ketua_tanda_tangan)]
        );
    }
}
