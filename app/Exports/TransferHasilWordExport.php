<?php

namespace App\Exports;

use App\Enums\JenisRplEnum;
use App\Enums\NilaiHurufEnum;
use App\Enums\StatusRplMataKuliahEnum;
use App\Models\PermohonanRpl;
use App\Services\NilaiKonversiService;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\Style\Table;

class TransferHasilWordExport
{
    public function __construct(
        private readonly NilaiKonversiService $nilaiKonversi,
    ) {}

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
            'marginTop'    => 1000,
            'marginBottom' => 1000,
            'marginLeft'   => 1200,
            'marginRight'  => 1200,
        ]);

        $this->addHeader($section, $permohonan);
        $this->addInfoTable($section, $permohonan);
        $section->addTextBreak(1);
        $this->addMkTable($section, $permohonan);

        return $phpWord;
    }

    private function addHeader(Section $section, PermohonanRpl $permohonan): void
    {
        $section->addText(
            'HASIL TRANSFER KREDIT',
            ['bold' => true, 'size' => 14],
            ['alignment' => 'center']
        );
        $section->addText(
            'Politeknik Caltex Riau',
            ['size' => 11],
            ['alignment' => 'center']
        );
        $section->addText(
            $permohonan->nomor_permohonan,
            ['size' => 10, 'color' => '5a6a75'],
            ['alignment' => 'center']
        );
        $section->addTextBreak(1);
    }

    private function addInfoTable(Section $section, PermohonanRpl $permohonan): void
    {
        $peserta = $permohonan->peserta;
        $user    = $peserta?->user;

        $tableStyle = ['borderSize' => 6, 'borderColor' => 'cccccc', 'cellMargin' => 80];
        $table = $section->addTable($tableStyle);

        $rows = [
            ['PT Asal',          ''],
            ['Prodi Asal',       ''],
            ['PT Tujuan',        'Politeknik Caltex Riau'],
            ['Prodi Tujuan',     $permohonan->programStudi?->nama ?? ''],
            ['Nama Mahasiswa',   $user?->nama ?? ''],
            ['Tanggal Lahir',    $user?->tanggal_lahir?->format('d/m/Y') ?? ''],
            ['NIM Asal',         ''],
            ['NIM Baru',         ''],
            ['Tahun Ajaran',     $permohonan->tahunAjaran?->nama ?? ''],
        ];

        foreach ($rows as [$label, $value]) {
            $table->addRow();
            $table->addCell(2500, ['bgColor' => 'F4F6F8'])->addText($label, ['bold' => true, 'size' => 10]);
            $table->addCell(5000)->addText($value, ['size' => 10]);
        }
    }

    private function addMkTable(Section $section, PermohonanRpl $permohonan): void
    {
        $section->addText('Rincian Mata Kuliah', ['bold' => true, 'size' => 11]);
        $section->addTextBreak(1);

        $tableStyle = [
            'borderSize'  => 6,
            'borderColor' => 'cccccc',
            'cellMargin'  => 60,
        ];
        $table = $section->addTable($tableStyle);

        // Header
        $headerCellStyle = ['bgColor' => '004B5F'];
        $headerTextStyle = ['bold' => true, 'size' => 9, 'color' => 'FFFFFF'];

        $table->addRow(400);
        $table->addCell(900, $headerCellStyle)->addText('Kode MK Asal', $headerTextStyle);
        $table->addCell(2000, $headerCellStyle)->addText('MK Asal', $headerTextStyle);
        $table->addCell(500, $headerCellStyle)->addText('SKS', $headerTextStyle);
        $table->addCell(900, $headerCellStyle)->addText('Kode MK Tujuan', $headerTextStyle);
        $table->addCell(2000, $headerCellStyle)->addText('MK Tujuan', $headerTextStyle);
        $table->addCell(500, $headerCellStyle)->addText('SKS', $headerTextStyle);
        $table->addCell(600, $headerCellStyle)->addText('Nilai', $headerTextStyle);
        $table->addCell(800, $headerCellStyle)->addText('Status', $headerTextStyle);

        $cellStyle = ['size' => 9];
        $bgAlt = ['bgColor' => 'F5F8FA'];

        foreach ($permohonan->rplMataKuliah as $i => $rplMk) {
            $mk          = $rplMk->mataKuliah;
            $matkulLampau = $rplMk->matkulLampau;
            $nilaiHuruf  = $this->resolveNilai($rplMk);
            $diakui      = $nilaiHuruf ? $nilaiHuruf->diakui() : null;

            $cellBg = ($i % 2 === 1) ? $bgAlt : [];

            if ($matkulLampau->isNotEmpty()) {
                $firstLampau = $matkulLampau->first();
                $table->addRow();
                $table->addCell(900, $cellBg)->addText($firstLampau->kode_mk, $cellStyle);
                $table->addCell(2000, $cellBg)->addText($firstLampau->nama_mk, $cellStyle);
                $table->addCell(500, $cellBg)->addText((string) $firstLampau->sks, $cellStyle);
                $table->addCell(900, $cellBg)->addText($mk->kode ?? '', $cellStyle);
                $table->addCell(2000, $cellBg)->addText($mk->nama ?? '', $cellStyle);
                $table->addCell(500, $cellBg)->addText((string) ($mk->sks ?? ''), $cellStyle);
                $table->addCell(600, $cellBg)->addText($nilaiHuruf?->value ?? '—', ['size' => 9, 'bold' => true, 'color' => $diakui ? '1e7e3e' : 'c62828']);
                $table->addCell(800, $cellBg)->addText($diakui === null ? '—' : ($diakui ? 'Diakui' : 'Tidak'), $cellStyle);

                // Extra rows for additional lampau MKs
                foreach ($matkulLampau->skip(1) as $lampau) {
                    $table->addRow();
                    $table->addCell(900, $cellBg)->addText($lampau->kode_mk, $cellStyle);
                    $table->addCell(2000, $cellBg)->addText($lampau->nama_mk, $cellStyle);
                    $table->addCell(500, $cellBg)->addText((string) $lampau->sks, $cellStyle);
                    $table->addCell(900, $cellBg)->addText('', $cellStyle);
                    $table->addCell(2000, $cellBg)->addText('', $cellStyle);
                    $table->addCell(500, $cellBg)->addText('', $cellStyle);
                    $table->addCell(600, $cellBg)->addText('', $cellStyle);
                    $table->addCell(800, $cellBg)->addText('', $cellStyle);
                }
            } else {
                // Tidak ada MK asal
                $table->addRow();
                $table->addCell(900, $cellBg)->addText('—', ['size' => 9, 'color' => '999999']);
                $table->addCell(2000, $cellBg)->addText('—', ['size' => 9, 'color' => '999999']);
                $table->addCell(500, $cellBg)->addText('—', ['size' => 9, 'color' => '999999']);
                $table->addCell(900, $cellBg)->addText($mk->kode ?? '', $cellStyle);
                $table->addCell(2000, $cellBg)->addText($mk->nama ?? '', $cellStyle);
                $table->addCell(500, $cellBg)->addText((string) ($mk->sks ?? ''), $cellStyle);
                $table->addCell(600, $cellBg)->addText($nilaiHuruf?->value ?? '—', ['size' => 9, 'bold' => true, 'color' => $diakui ? '1e7e3e' : 'c62828']);
                $table->addCell(800, $cellBg)->addText($diakui === null ? '—' : ($diakui ? 'Diakui' : 'Tidak'), $cellStyle);
            }
        }
    }

    private function resolveNilai(\App\Models\RplMataKuliah $rplMk): ?NilaiHurufEnum
    {
        // Jika sudah ada nilai_transfer langsung
        if ($rplMk->nilai_transfer) {
            return NilaiHurufEnum::from($rplMk->nilai_transfer);
        }

        // Fallback: konversi rata-rata asesor
        $nilaiList = $rplMk->asesmenMandiri
            ->map(fn($asm) => $asm->nilaiAsesor?->nilai)
            ->filter(fn($v) => $v !== null);

        if ($nilaiList->isEmpty()) {
            return null;
        }

        return $this->nilaiKonversi->toHuruf($nilaiList->average());
    }
}
