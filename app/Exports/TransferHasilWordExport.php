<?php

namespace App\Exports;

use App\Enums\JenisRplEnum;
use App\Enums\NilaiHurufEnum;
use App\Enums\StatusRplMataKuliahEnum;
use App\Models\Penandatangan;
use App\Models\PermohonanRpl;
use App\Models\ProgramStudi;
use App\Services\NilaiKonversiService;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\PhpWord;

class TransferHasilWordExport
{
    public function __construct(
        private readonly NilaiKonversiService $nilaiKonversi,
        private readonly ?Penandatangan $penandatanganWadir = null,
        private readonly ?ProgramStudi $programStudiKetua = null,
    ) {
    }

    public function generate(PermohonanRpl $permohonan): PhpWord
    {
        abort_if($permohonan->jenis_rpl !== JenisRplEnum::RplI, 422, 'Hanya untuk Transfer Kredit.');

        $permohonan->loadMissing([
            'peserta.user',
            'programStudi',
            'tahunAjaran',
            'rplMataKuliah.mataKuliah',
            'rplMataKuliah.matkulLampau',
            'rplMataKuliah.asesmenMandiri.nilaiAsesor',
        ]);

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(11);

        $section = $phpWord->addSection([
            'marginTop' => 1000,
            'marginBottom' => 1000,
            'marginLeft' => 1200,
            'marginRight' => 1200,
        ]);

        $this->addHeader($section, $permohonan);
        $this->addInfoSection($section, $permohonan);
        $section->addTextBreak(1);
        $this->addMkTable($section, $permohonan);
        $section->addTextBreak(1);
        $this->addFooterTotals($section, $permohonan);
        $section->addTextBreak(2);
        $this->addTandaTangan($section);

        return $phpWord;
    }

    private function addHeader(Section $section, PermohonanRpl $permohonan): void
    {
        $section->addText(
            'DAFTAR HASIL KONVERSI NILAI HASIL STUDI',
            ['bold' => true, 'size' => 14],
            ['alignment' => 'center']
        );
        $section->addText(
            $this->safeText('No. ' . $permohonan->nomor_permohonan),
            ['size' => 11],
            ['alignment' => 'center']
        );
        $section->addTextBreak(1);
    }

    private function addInfoSection(Section $section, PermohonanRpl $permohonan): void
    {
        $peserta = $permohonan->peserta;
        $user = $peserta?->user;

        $section->addText('Data Konversi Nilai Hasil Studi', ['bold' => true, 'size' => 11]);
        $section->addTextBreak(1);

        $tableStyle = ['borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 60];
        $table = $section->addTable($tableStyle);

        $rows = [
            // Blok PT Asal
            ['Perguruan Tinggi Asal', $peserta?->institusi_asal ?? '—'],
            ['Program Studi', $peserta?->program_studi_asal ?? '—'],
            ['Peringkat Akreditasi', $peserta?->peringkat_akreditasi_asal ?? '—'],
            // Spacer
            ['', ''],
            // Blok PT Tujuan
            ['Perguruan Tinggi Tujuan', 'Politeknik Caltex Riau'],
            ['Program Studi', $permohonan->programStudi?->nama ?? ''],
            ['Peringkat Akreditasi', ''],
            // Spacer
            ['', ''],
            // Info mahasiswa
            ['Nama Mahasiswa', $user?->nama ?? ''],
            ['Tempat/Tanggal Lahir', ($peserta?->tempat_lahir ?? '') . '/' . ($peserta?->tanggal_lahir?->format('d/m/Y') ?? '')],
            ['NIM Perguruan Tinggi Asal', ''],
            ['NIM Perguruan Tinggi Baru', ''],
        ];

        $labelStyle = ['size' => 10];
        $valueStyle = ['size' => 10];

        foreach ($rows as [$label, $value]) {
            $table->addRow();
            $table->addCell(2800)->addText($label, $labelStyle);
            $table->addCell(300)->addText($label ? ':' : '', $labelStyle);
            $table->addCell(5000)->addText($this->safeText($value), $valueStyle);
        }
    }

    private function addMkTable(Section $section, PermohonanRpl $permohonan): void
    {
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 60,
        ];
        $table = $section->addTable($tableStyle);

        $headerCellStyle = ['bgColor' => 'D9D9D9'];
        $headerTextStyle = ['bold' => true, 'size' => 9];
        $center = ['alignment' => 'center'];

        $table->addRow(400);
        $table->addCell(900, $headerCellStyle)->addText('Kode MK Asal', $headerTextStyle, $center);
        $table->addCell(2200, $headerCellStyle)->addText('Mata Kuliah Asal', $headerTextStyle, $center);
        $table->addCell(500, $headerCellStyle)->addText('SKS', $headerTextStyle, $center);
        $table->addCell(700, $headerCellStyle)->addText('Nilai Huruf', $headerTextStyle, $center);
        $table->addCell(900, $headerCellStyle)->addText('Kode MK Tujuan', $headerTextStyle, $center);
        $table->addCell(2200, $headerCellStyle)->addText('Mata Kuliah Tujuan', $headerTextStyle, $center);
        $table->addCell(500, $headerCellStyle)->addText('SKS', $headerTextStyle, $center);
        $table->addCell(700, $headerCellStyle)->addText('Nilai Huruf', $headerTextStyle, $center);

        $cellStyle = ['size' => 9];
        $cellCenter = ['alignment' => 'center'];

        // Only show MK with status Diakui
        $mkDiakui = $permohonan->rplMataKuliah
            ->where('status', StatusRplMataKuliahEnum::Diakui);

        foreach ($mkDiakui as $rplMk) {
            $mk = $rplMk->mataKuliah;
            $lampauList = $rplMk->matkulLampau;
            $nilaiTujuan = $this->resolveNilai($rplMk);

            if ($lampauList->isNotEmpty()) {
                foreach ($lampauList as $idx => $lampau) {
                    $table->addRow();
                    $table->addCell(900)->addText($this->safeText($lampau->kode_mk), $cellStyle);
                    $table->addCell(2200)->addText($this->safeText($lampau->nama_mk), $cellStyle);
                    $table->addCell(500)->addText($this->safeText((string) $lampau->sks), $cellStyle, $cellCenter);
                    $table->addCell(700)->addText($this->safeText($lampau->nilai_huruf?->value ?? ''), ['size' => 9, 'bold' => true], $cellCenter);

                    // MK Tujuan hanya di baris pertama
                    if ($idx === 0) {
                        $table->addCell(900)->addText($this->safeText($mk->kode ?? ''), $cellStyle);
                        $table->addCell(2200)->addText($this->safeText($mk->nama ?? ''), $cellStyle);
                        $table->addCell(500)->addText($this->safeText((string) ($mk->sks ?? '')), $cellStyle, $cellCenter);
                        $table->addCell(700)->addText($this->safeText($nilaiTujuan?->value ?? ''), ['size' => 9, 'bold' => true], $cellCenter);
                    } else {
                        $table->addCell(900)->addText('', $cellStyle);
                        $table->addCell(2200)->addText('', $cellStyle);
                        $table->addCell(500)->addText('', $cellStyle);
                        $table->addCell(700)->addText('', $cellStyle);
                    }
                }
            } else {
                // Jika MK lampau kosong, pakai data MK eksisting untuk kolom asal.
                $table->addRow();
                $table->addCell(900)->addText($this->safeText($mk->kode ?? ''), $cellStyle);
                $table->addCell(2200)->addText($this->safeText($mk->nama ?? ''), $cellStyle);
                $table->addCell(500)->addText($this->safeText((string) ($mk->sks ?? '')), $cellStyle, $cellCenter);
                $table->addCell(700)->addText($this->safeText($nilaiTujuan?->value ?? ''), ['size' => 9, 'bold' => true], $cellCenter);
                $table->addCell(900)->addText($this->safeText($mk->kode ?? ''), $cellStyle);
                $table->addCell(2200)->addText($this->safeText($mk->nama ?? ''), $cellStyle);
                $table->addCell(500)->addText($this->safeText((string) ($mk->sks ?? '')), $cellStyle, $cellCenter);
                $table->addCell(700)->addText($this->safeText($nilaiTujuan?->value ?? ''), ['size' => 9, 'bold' => true], $cellCenter);
            }
        }
    }

    private function addFooterTotals(Section $section, PermohonanRpl $permohonan): void
    {
        // Only count from Diakui MK
        $mkDiakui = $permohonan->rplMataKuliah
            ->where('status', StatusRplMataKuliahEnum::Diakui);

        $totalSksAsal = $mkDiakui
            ->flatMap(fn($rplMk) => $rplMk->matkulLampau)
            ->sum('sks');

        $sksDiakui = $mkDiakui->sum(fn($rplMk) => $rplMk->mataKuliah->sks ?? 0);

        $totalSksProdi = $permohonan->programStudi?->total_sks ?? 0;

        $tableStyle = ['borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 40];
        $table = $section->addTable($tableStyle);

        $boldStyle = ['bold' => true, 'size' => 10];

        $rows = [
            ['Total SKS Asal', $totalSksAsal . ' SKS'],
            ['Total SKS Diakui', $sksDiakui . ' SKS'],
            ['Total SKS yang harus diambil', ($totalSksProdi - $sksDiakui) . ' SKS'],
            ['Total SKS Sarjana Terapan', $totalSksProdi . ' SKS'],
        ];

        foreach ($rows as [$label, $value]) {
            $table->addRow();
            $table->addCell(3200)->addText($label, $boldStyle);
            $table->addCell(300)->addText(':', ['size' => 10]);
            $table->addCell(3000)->addText($this->safeText($value), ['size' => 10]);
        }
    }

    private function addTandaTangan(Section $section): void
    {
        $tanggal = now()->locale('id')->translatedFormat('d F Y');

        $table = $section->addTable(['borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 60]);
        $table->addRow();

        $cellKiri = $table->addCell(4000);
        $cellKanan = $table->addCell(4000);

        // Kiri: Wakil Direktur
        $cellKiri->addText('Mengetahui,', ['size' => 10]);
        $cellKiri->addText($this->safeText($this->penandatanganWadir?->jabatan ?? 'Wakil Direktur Bidang Akademik'), ['size' => 10]);
        $cellKiri->addTextBreak(1);
        $this->addTtdImage($cellKiri, $this->penandatanganWadir?->tanda_tangan ?? null);
        $cellKiri->addTextBreak(1);
        $cellKiri->addText($this->safeText($this->penandatanganWadir?->nama ?? ''), ['bold' => true, 'size' => 10]);
        if ($this->penandatanganWadir?->nip) {
            $cellKiri->addText($this->safeText('NIP. ' . $this->penandatanganWadir->nip), ['size' => 10]);
        }

        // Kanan: Ketua Program Studi
        $cellKanan->addText($this->safeText('Pekanbaru, ' . $tanggal), ['size' => 10]);
        $cellKanan->addText($this->safeText($this->programStudiKetua?->ketua_jabatan ?? 'Ketua Program Studi'), ['size' => 10]);
        $cellKanan->addTextBreak(1);
        $this->addTtdImage($cellKanan, $this->programStudiKetua?->ketua_tanda_tangan ?? null);
        $cellKanan->addTextBreak(1);
        $cellKanan->addText($this->safeText($this->programStudiKetua?->ketua_nama ?? ''), ['bold' => true, 'size' => 10]);
        if ($this->programStudiKetua?->ketua_nip) {
            $cellKanan->addText($this->safeText('NIP. ' . $this->programStudiKetua->ketua_nip), ['size' => 10]);
        }
    }

    private function addTtdImage($cell, ?string $ttdPath): void
    {
        if (! $ttdPath) {
            $cell->addTextBreak(3);
            return;
        }

        $disk = Storage::disk('local');
        if (! $disk->exists($ttdPath)) {
            $cell->addTextBreak(3);
            return;
        }

        $path = $disk->path($ttdPath);
        if (! $this->isValidImage($path)) {
            $cell->addTextBreak(3);
            return;
        }

        try {
            $cell->addImage(
                $path,
                ['width' => 100, 'height' => 50, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::START]
            );
        } catch (\Exception) {
            $cell->addTextBreak(3);
        }
    }

    private function isValidImage(string $path): bool
    {
        if (! is_file($path) || ! is_readable($path)) {
            return false;
        }

        $size = @filesize($path);
        if ($size === false || $size === 0) {
            return false;
        }

        if (function_exists('exif_imagetype')) {
            return exif_imagetype($path) !== false;
        }

        return is_array(@getimagesize($path));
    }

    private function safeText(?string $text): string
    {
        if ($text === null) {
            return '';
        }

        $value = (string) $text;
        if ($value === '') {
            return '';
        }

        if (function_exists('mb_convert_encoding')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
        } elseif (function_exists('utf8_encode')) {
            $value = utf8_encode($value);
        }

        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value);

        return $value ?? '';
    }

    private function resolveNilai(\App\Models\RplMataKuliah $rplMk): ?NilaiHurufEnum
    {
        if ($rplMk->nilai_transfer) {
            return NilaiHurufEnum::from($rplMk->nilai_transfer);
        }

        $nilaiList = $rplMk->asesmenMandiri
            ->map(fn($asm) => $asm->nilaiAsesor?->nilai)
            ->filter(fn($v) => $v !== null);

        if ($nilaiList->isEmpty()) {
            return null;
        }

        return $this->nilaiKonversi->toHuruf($nilaiList->average());
    }
}
