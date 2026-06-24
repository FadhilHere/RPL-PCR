<?php

namespace App\Exports;

use App\Enums\JenisRplEnum;
use App\Enums\StatusRplMataKuliahEnum;
use App\Models\PermohonanRpl;
use App\Models\RplMataKuliah;
use App\Services\NilaiKonversiService;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\SimpleType\Jc;

class FormAsesmenWordExport
{
    public function __construct(
        private readonly NilaiKonversiService $nilaiKonversi,
    ) {
    }

    public function generate(PermohonanRpl $permohonan): PhpWord
    {
        $permohonan->loadMissing([
            'peserta.user',
            'programStudi',
            'tahunAjaran',
            'asesor.user',
            'rplMataKuliah.mataKuliah',
            'rplMataKuliah.asesmenMandiri.pertanyaan',
            'rplMataKuliah.asesmenMandiri.nilaiAsesor',
            'rplMataKuliah.asesmenMandiri.evaluasiVatm',
            'rplMataKuliah.matkulLampau',
        ]);

        $phpWord = new PhpWord();
        Settings::setOutputEscapingEnabled(true);
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

        $isTransfer = $permohonan->jenis_rpl === JenisRplEnum::RplI;

        foreach ($permohonan->rplMataKuliah as $rplMk) {
            $this->addMkBlock($section, $rplMk, $isTransfer);
            $section->addTextBreak(1);
        }

        $this->addFooterTotals($section, $permohonan);
        $section->addTextBreak(1);
        $this->addTandaTangan($section, $permohonan);

        return $phpWord;
    }

    private function addHeader(Section $section, PermohonanRpl $permohonan): void
    {
        $section->addText(
            'FORM ASESMEN RPL',
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

        $semesterLabel = $permohonan->semester?->label();
        $tahunAjaran = $permohonan->tahunAjaran?->nama ?? '';
        if ($semesterLabel) {
            $tahunAjaran = trim($tahunAjaran . ' — Semester ' . $semesterLabel);
        }

        $tableStyle = ['borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 60];
        $table = $section->addTable($tableStyle);

        $rows = [
            ['Nama Peserta', $user?->nama ?? ''],
            ['Program Studi', $permohonan->programStudi?->nama ?? ''],
            ['Jenis RPL', $permohonan->jenis_rpl?->label() ?? ''],
            ['Tahun Ajaran', $tahunAjaran],
            ['No. Permohonan', $permohonan->nomor_permohonan ?? ''],
        ];

        $labelStyle = ['size' => 10];
        $valueStyle = ['size' => 10];

        foreach ($rows as [$label, $value]) {
            $table->addRow();
            $table->addCell(2800)->addText($label, $labelStyle);
            $table->addCell(300)->addText(':', $labelStyle);
            $table->addCell(5000)->addText($this->safeText($value), $valueStyle);
        }
    }

    private function addMkBlock(Section $section, RplMataKuliah $rplMk, bool $isTransfer): void
    {

        $mk = $rplMk->mataKuliah;

        $judul = trim(($mk?->kode ? $mk->kode . ' — ' : '') . ($mk?->nama ?? ''));
        if ($mk?->sks) {
            $judul .= ' (' . $mk->sks . ' SKS)';
        }
        $section->addText($this->safeText($judul), ['bold' => true, 'size' => 11]);

        if ($isTransfer) {
            $this->addTransferTable($section, $rplMk);
        } elseif ($rplMk->matkulLampau->isNotEmpty()) {
            $this->addMatkulLampauSection($section, $rplMk);
        } elseif ($rplMk->asesmenMandiri->isNotEmpty()) {
            $this->addAsesmenTable($section, $rplMk);
        } else {
            $section->addText('Belum ada data penilaian.', ['size' => 9, 'italic' => true]);
        }

        // Status akhir MK
        $statusLabel = $rplMk->status?->label() ?? StatusRplMataKuliahEnum::Menunggu->label();
        $section->addText(
            $this->safeText('Status Mata Kuliah: ' . $statusLabel),
            ['size' => 10, 'bold' => true]
        );

        // Catatan asesor tingkat MK (rich-text Quill → strip HTML)
        $catatanMk = trim(strip_tags((string) ($rplMk->catatan_asesor ?? '')));
        if ($catatanMk !== '') {
            $section->addText(
                $this->safeText('Catatan Asesor: ' . $catatanMk),
                ['size' => 10]
            );
        }
    }

    private function addAsesmenTable(Section $section, RplMataKuliah $rplMk): void
    {
        $tableStyle = ['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 60];
        $table = $section->addTable($tableStyle);

        $headerCellStyle = ['bgColor' => 'D9D9D9'];
        $headerTextStyle = ['bold' => true, 'size' => 9];
        $center = ['alignment' => 'center'];

        $table->addRow(400);
        $table->addCell(400, $headerCellStyle)->addText('No', $headerTextStyle, $center);
        $table->addCell(3800, $headerCellStyle)->addText('Sub-Kompetensi', $headerTextStyle, $center);
        $table->addCell(700, $headerCellStyle)->addText('Nilai Diri', $headerTextStyle, $center);
        $table->addCell(700, $headerCellStyle)->addText('Nilai Asesor', $headerTextStyle, $center);
        $table->addCell(400, $headerCellStyle)->addText('V', $headerTextStyle, $center);
        $table->addCell(400, $headerCellStyle)->addText('A', $headerTextStyle, $center);
        $table->addCell(400, $headerCellStyle)->addText('T', $headerTextStyle, $center);
        $table->addCell(400, $headerCellStyle)->addText('M', $headerTextStyle, $center);

        $cellStyle = ['size' => 9];
        $cellCenter = ['alignment' => 'center'];

        $nilaiList = collect();

        foreach ($rplMk->asesmenMandiri as $idx => $asm) {
            $vatm = $asm->evaluasiVatm;
            $nilaiAsesor = $asm->nilaiAsesor?->nilai;
            if ($nilaiAsesor !== null) {
                $nilaiList->push($nilaiAsesor);
            }

            $table->addRow();
            $table->addCell(400)->addText((string) ($idx + 1), $cellStyle, $cellCenter);
            $table->addCell(3800)->addText($this->safeText($asm->pertanyaan?->pertanyaan ?? ''), $cellStyle);
            $table->addCell(700)->addText($this->safeText((string) ($asm->penilaian_diri ?? '—')), $cellStyle, $cellCenter);
            $table->addCell(700)->addText($this->safeText($nilaiAsesor !== null ? (string) $nilaiAsesor : '—'), ['size' => 9, 'bold' => true], $cellCenter);
            $table->addCell(400)->addText($this->vatmMark($vatm?->valid), $cellStyle, $cellCenter);
            $table->addCell(400)->addText($this->vatmMark($vatm?->autentik), $cellStyle, $cellCenter);
            $table->addCell(400)->addText($this->vatmMark($vatm?->terkini), $cellStyle, $cellCenter);
            $table->addCell(400)->addText($this->vatmMark($vatm?->memadai), $cellStyle, $cellCenter);
        }

        $section->addTextBreak(1);

        $nilaiTransfer = $this->resolveNilaiTransfer($rplMk);
        if ($nilaiTransfer !== '') {
            $section->addText(
                $this->safeText("Nilai: {$nilaiTransfer}"),
                ['size' => 10, 'bold' => true]
            );
        } elseif ($nilaiList->isNotEmpty()) {
            $nilaiHuruf = $this->nilaiKonversi->toHuruf($nilaiList->average());

            $section->addText(
                $this->safeText('Nilai: ' . $nilaiHuruf->value),
                ['size' => 10, 'bold' => true]
            );
        } else {
            $section->addText(
                'Nilai: belum dinilai',
                ['size' => 10, 'italic' => true]
            );
        }
    }

    private function addTransferTable(Section $section, RplMataKuliah $rplMk): void
    {
        $tableStyle = ['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 60];
        $table = $section->addTable($tableStyle);

        $headerCellStyle = ['bgColor' => 'D9D9D9'];
        $headerTextStyle = ['bold' => true, 'size' => 9];
        $center = ['alignment' => 'center'];

        $table->addRow(400);
        $table->addCell(900, $headerCellStyle)->addText('Kode MK Asal', $headerTextStyle, $center);
        $table->addCell(2400, $headerCellStyle)->addText('Mata Kuliah Asal', $headerTextStyle, $center);
        $table->addCell(500, $headerCellStyle)->addText('SKS', $headerTextStyle, $center);
        $table->addCell(700, $headerCellStyle)->addText('Nilai', $headerTextStyle, $center);
        $table->addCell(900, $headerCellStyle)->addText('Kode MK Tujuan', $headerTextStyle, $center);
        $table->addCell(2400, $headerCellStyle)->addText('Mata Kuliah Tujuan', $headerTextStyle, $center);
        $table->addCell(500, $headerCellStyle)->addText('SKS', $headerTextStyle, $center);
        $table->addCell(700, $headerCellStyle)->addText('Nilai', $headerTextStyle, $center);

        $cellStyle = ['size' => 9];
        $cellCenter = ['alignment' => 'center'];

        $mk = $rplMk->mataKuliah;
        $nilaiTransfer = $this->resolveNilaiTransfer($rplMk);

        foreach ($rplMk->matkulLampau as $idx => $lampau) {
            $table->addRow();
            $table->addCell(900)->addText($this->safeText($lampau->kode_mk_asesor ?? $lampau->kode_mk ?? ''), $cellStyle);
            $table->addCell(2400)->addText($this->safeText($lampau->nama_mk_asesor ?? $lampau->nama_mk ?? ''), $cellStyle);
            $table->addCell(500)->addText($this->safeText((string) ($lampau->sks_asesor ?? $lampau->sks ?? '')), $cellStyle, $cellCenter);
            $table->addCell(700)->addText($this->safeText(
                (string) ($lampau->nilai_huruf_asesor?->value ?? $lampau->nilai_huruf?->value ?? '')
            ), ['size' => 9, 'bold' => true], $cellCenter);

            if ($idx === 0) {
                $table->addCell(900)->addText($this->safeText($mk?->kode ?? ''), $cellStyle);
                $table->addCell(2400)->addText($this->safeText($mk?->nama ?? ''), $cellStyle);
                $table->addCell(500)->addText($this->safeText((string) ($mk?->sks ?? '')), $cellStyle, $cellCenter);
                $table->addCell(700)->addText($this->safeText($nilaiTransfer), ['size' => 9, 'bold' => true], $cellCenter);
            } else {
                $table->addCell(900)->addText('', $cellStyle);
                $table->addCell(2400)->addText('', $cellStyle);
                $table->addCell(500)->addText('', $cellStyle);
                $table->addCell(700)->addText('', $cellStyle);
            }
        }

        $section->addTextBreak(1);

        if ($nilaiTransfer !== '') {
            $section->addText(
                $this->safeText('Nilai: ' . $nilaiTransfer),
                ['size' => 10, 'bold' => true]
            );
        } else {
            $section->addText('Nilai: belum dinilai', ['size' => 10, 'italic' => true]);
        }

        foreach ($rplMk->matkulLampau as $lampau) {
            $catatan = trim(strip_tags((string) ($lampau->catatan_asesor ?? '')));
            if ($catatan !== '') {
                $section->addText(
                    $this->safeText('Catatan (' . ($lampau->kode_mk_final ?? '—') . '): ' . $catatan),
                    ['size' => 10]
                );
            }
        }
    }

    private function addMatkulLampauSection(Section $section, RplMataKuliah $rplMk): void
    {
        $tableStyle = ['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 60];
        $table = $section->addTable($tableStyle);

        $headerCellStyle = ['bgColor' => 'D9D9D9'];
        $headerTextStyle = ['bold' => true, 'size' => 9];
        $center = ['alignment' => 'center'];

        $table->addRow(400);
        $table->addCell(400, $headerCellStyle)->addText('No', $headerTextStyle, $center);
        $table->addCell(3800, $headerCellStyle)->addText('Sub-Kompetensi', $headerTextStyle, $center);
        $table->addCell(700, $headerCellStyle)->addText('Nilai Diri', $headerTextStyle, $center);
        $table->addCell(700, $headerCellStyle)->addText('Nilai Asesor', $headerTextStyle, $center);
        $table->addCell(400, $headerCellStyle)->addText('V', $headerTextStyle, $center);
        $table->addCell(400, $headerCellStyle)->addText('A', $headerTextStyle, $center);
        $table->addCell(400, $headerCellStyle)->addText('T', $headerTextStyle, $center);
        $table->addCell(400, $headerCellStyle)->addText('M', $headerTextStyle, $center);

        $cellStyle = ['size' => 9];
        $cellCenter = ['alignment' => 'center'];

        $nilaiList = collect();

        foreach ($rplMk->asesmenMandiri as $idx => $asm) {
            $vatm = $asm->evaluasiVatm;
            $nilaiAsesor = $asm->nilaiAsesor?->nilai;
            if ($nilaiAsesor !== null) {
                $nilaiList->push($nilaiAsesor);
            }

            $table->addRow();
            $table->addCell(400)->addText((string) ($idx + 1), $cellStyle, $cellCenter);
            $table->addCell(3800)->addText($this->safeText($asm->pertanyaan?->pertanyaan ?? ''), $cellStyle);
            $table->addCell(700)->addText($this->safeText((string) ($asm->penilaian_diri ?? '—')), $cellStyle, $cellCenter);
            $table->addCell(700)->addText($this->safeText($nilaiAsesor !== null ? (string) $nilaiAsesor : '—'), ['size' => 9, 'bold' => true], $cellCenter);
            $table->addCell(400)->addText($this->vatmMark($vatm?->valid), $cellStyle, $cellCenter);
            $table->addCell(400)->addText($this->vatmMark($vatm?->autentik), $cellStyle, $cellCenter);
            $table->addCell(400)->addText($this->vatmMark($vatm?->terkini), $cellStyle, $cellCenter);
            $table->addCell(400)->addText($this->vatmMark($vatm?->memadai), $cellStyle, $cellCenter);
        }


        $nilaiMKLampau = $this->resolveNilaiMKLampau($rplMk);
        if ($nilaiMKLampau !== null) {
            $section->addText(
                $this->safeText('Nilai: ' . $nilaiMKLampau),
                ['size' => 10, 'bold' => true]
            );
        }

        foreach ($rplMk->matkulLampau as $lampau) {
            $catatan = trim(strip_tags((string) ($lampau->catatan_asesor ?? '')));
            if ($catatan !== '') {
                $section->addText(
                    $this->safeText('Catatan Asesor: ') . $catatan,
                    ['size' => 10]
                );
            }
        }
    }

    private function resolveNilaiMKLampau(RplMataKuliah $rplMk): ?string
    {
        $nilaiTransfer = trim((string) ($rplMk->nilai_transfer ?? ''));

        if ($nilaiTransfer !== '') {
            return $nilaiTransfer;
        }

        $nilaiList = $rplMk->asesmenMandiri
            ->map(fn($asm) => $asm->nilaiAsesor?->nilai)
            ->filter(fn($v) => $v !== null);

        if ($nilaiList->isEmpty()) {
            return null;
        }

        return $this->nilaiKonversi->toHuruf($nilaiList->average())->value;
    }

    private function resolveNilaiTransfer(RplMataKuliah $rplMk): string
    {
        $rawValue = $rplMk->getRawOriginal('nilai_transfer');

        if (is_string($rawValue) && trim($rawValue) !== '') {
            return trim($rawValue);
        }

        $attributeValue = $rplMk->nilai_transfer;

        if (is_string($attributeValue) && trim($attributeValue) !== '') {
            return trim($attributeValue);
        }

        return '';
    }

    private function addFooterTotals(Section $section, PermohonanRpl $permohonan): void
    {
        $sksDiakui = $permohonan->rplMataKuliah
            ->where('status', StatusRplMataKuliahEnum::Diakui)
            ->sum(fn($rplMk) => $rplMk->mataKuliah->sks ?? 0);

        $totalSksProdi = $permohonan->programStudi?->total_sks ?? 0;

        $tableStyle = ['borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 40];
        $table = $section->addTable($tableStyle);

        $boldStyle = ['bold' => true, 'size' => 10];

        $rows = [
            ['Total SKS Diakui', $sksDiakui . ' SKS'],
            ['Total SKS Prodi', $totalSksProdi . ' SKS'],
        ];

        foreach ($rows as [$label, $value]) {
            $table->addRow();
            $table->addCell(3200)->addText($label, $boldStyle);
            $table->addCell(300)->addText(':', ['size' => 10]);
            $table->addCell(3000)->addText($this->safeText($value), ['size' => 10]);
        }
    }

    private function addTandaTangan(Section $section, PermohonanRpl $permohonan): void
    {
        $asesorList = $permohonan->asesor;

        if (!$asesorList || $asesorList->isEmpty()) {
            return;
        }

        $tanggal = now()->locale('id')->translatedFormat('d F Y');

        $table = $section->addTable(['borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 60]);
        $table->addRow();

        // Kolom kiri kosong sebagai spacer
        $table->addCell(6500)->addText('');

        $cellKanan = $table->addCell(3000);
        $cellKanan->addText($this->safeText('Pekanbaru, ' . $tanggal), ['size' => 10]);
        $cellKanan->addText('Asesor,', ['size' => 10]);

        foreach ($asesorList as $asesor) {
            $cellKanan->addTextBreak(1);
            $this->addTtdImage($cellKanan, $asesor->tanda_tangan ?? null);
            $cellKanan->addTextBreak(1);
            $cellKanan->addText($this->safeText($asesor->user?->nama ?? ''), ['bold' => true, 'size' => 10]);
            if ($asesor->nidn) {
                $cellKanan->addText($this->safeText('NIDN. ' . $asesor->nidn), ['size' => 10]);
            }
        }
    }

    private function addTtdImage($cell, ?string $ttdPath): void
    {
        if (!$ttdPath) {
            $cell->addTextBreak(3);
            return;
        }

        $disk = Storage::disk('local');
        if (!$disk->exists($ttdPath)) {
            $cell->addTextBreak(3);
            return;
        }

        $path = $disk->path($ttdPath);
        if (!$this->isValidImage($path)) {
            $cell->addTextBreak(3);
            return;
        }

        try {
            $cell->addImage($path, ['width' => 100, 'height' => 50, 'alignment' => Jc::START]);
        } catch (\Exception) {
            $cell->addTextBreak(3);
        }
    }

    private function isValidImage(string $path): bool
    {
        if (!is_file($path) || !is_readable($path)) {
            return false;
        }

        $size = @filesize($path);
        if ($size === false || $size === 0) {
            return false;
        }

        if (function_exists('exif_imagetype')) {
            return exif_imagetype($path) !== false;
        }

        return \is_array(@getimagesize($path));
    }

    private function vatmMark(?bool $value): string
    {
        return $value ? '✓' : '-';
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
}
